<?php

namespace App\Http\Controllers;

use App\Models\LabResult;
use App\Models\LabRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LabResultController extends Controller
{
    /**
     * Display a listing of lab results.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $labResults = LabResult::with(['labRequest.patient', 'labRequest.laboratory', 'technician'])
            ->when($request->lab_request_id, function ($query) use ($request) {
                return $query->where('lab_request_id', $request->lab_request_id);
            })
            ->when($request->technician_id, function ($query) use ($request) {
                return $query->where('technician_id', $request->technician_id);
            })
            // Filter based on user role
            ->when($user && $user->role === 'patient', function ($query) use ($user) {
                return $query->whereHas('labRequest', function ($q) use ($user) {
                    $q->where('patient_id', $user->id);
                });
            })
            ->when($user && ($user->role === 'doctor' || $user->role === 'nurse'), function ($query) use ($user) {
                return $query->whereHas('labRequest', function ($q) use ($user) {
                    $q->where('provider_id', function ($subquery) use ($user) {
                        $subquery->select('id')
                                 ->from('healthcare_providers')
                                 ->where('user_id', $user->id);
                    });
                });
            })
            ->when($user && $user->role === 'lab_technician', function ($query) use ($user) {
                return $query->where('technician_id', $user->id);
            })
            ->orderBy('result_date', 'desc')
            ->paginate($request->per_page ?? 15);
            
        return response()->json($labResults);
    }

    /**
     * Store a newly created lab result.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lab_request_id' => 'required|exists:lab_requests,id',
            'results' => 'required|json',
            'technician_id' => 'required|exists:users,id',
            'result_date' => 'required|date',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
            'notes' => 'nullable|string',
        ]);
        
        // Check if result already exists for this request
        $existingResult = LabResult::where('lab_request_id', $validated['lab_request_id'])->first();
        if ($existingResult) {
            return response()->json([
                'message' => 'A result already exists for this lab request',
                'result' => $existingResult
            ], 422);
        }
        
        // Check if the technician is a lab technician
        $technician = User::findOrFail($validated['technician_id']);
        if ($technician->role !== 'lab_technician') {
            return response()->json([
                'message' => 'The selected user is not a lab technician'
            ], 422);
        }
        
        // Update lab request status to completed
        $labRequest = LabRequest::findOrFail($validated['lab_request_id']);
        $labRequest->update(['status' => 'completed']);
        
        // Handle file upload if provided
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('lab_results', 'public');
            $validated['file_url'] = $path;
        }
        
        $labResult = LabResult::create($validated);
        
        return response()->json($labResult->load(['labRequest.patient', 'labRequest.laboratory', 'technician']), 201);
    }

    /**
     * Display the specified lab result.
     */
    public function show(LabResult $labResult)
    {
        return response()->json($labResult->load(['labRequest.patient', 'labRequest.laboratory', 'technician']));
    }

    /**
     * Update the specified lab result.
     */
    public function update(Request $request, LabResult $labResult)
    {
        $validated = $request->validate([
            'results' => 'sometimes|json',
            'result_date' => 'sometimes|date',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
            'notes' => 'nullable|string',
        ]);
        
        // Handle file upload if provided
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($labResult->file_url) {
                Storage::disk('public')->delete($labResult->file_url);
            }
            
            $path = $request->file('file')->store('lab_results', 'public');
            $validated['file_url'] = $path;
        }
        
        $labResult->update($validated);
        
        return response()->json($labResult);
    }

    /**
     * Remove the specified lab result.
     */
    public function destroy(LabResult $labResult)
    {
        // Delete file if exists
        if ($labResult->file_url) {
            Storage::disk('public')->delete($labResult->file_url);
        }
        
        // Update associated lab request status to processing
        $labRequest = $labResult->labRequest;
        $labRequest->update(['status' => 'processing']);
        
        $labResult->delete();
        
        return response()->json(null, 204);
    }
}