<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MedicalRecordController extends Controller
{
    /**
     * Display a listing of medical records.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $records = MedicalRecord::with(['patient', 'provider.user', 'hospital'])
            ->when($request->patient_id, function ($query) use ($request) {
                return $query->where('patient_id', $request->patient_id);
            })
            ->when($request->provider_id, function ($query) use ($request) {
                return $query->where('provider_id', $request->provider_id);
            })
            ->when($request->hospital_id, function ($query) use ($request) {
                return $query->where('hospital_id', $request->hospital_id);
            })
            ->when($request->record_type, function ($query) use ($request) {
                return $query->where('record_type', $request->record_type);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->from_date && $request->to_date, function ($query) use ($request) {
                return $query->whereBetween('date', [$request->from_date, $request->to_date]);
            })
            // Filter based on user role
            ->when($user && $user->role === 'patient', function ($query) use ($user) {
                return $query->where('patient_id', $user->id);
            })
            ->when($user && ($user->role === 'doctor' || $user->role === 'nurse'), function ($query) use ($user) {
                return $query->where('provider_id', function ($subquery) use ($user) {
                    $subquery->select('id')
                             ->from('healthcare_providers')
                             ->where('user_id', $user->id);
                });
            })
            ->orderBy('date', 'desc')
            ->paginate($request->per_page ?? 15);
            
        return response()->json($records);
    }

    /**
     * Store a newly created medical record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'record_type' => 'required|in:medical_report,lab_result,vaccination,prescription',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
            'provider_id' => 'nullable|exists:healthcare_providers,id',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'date' => 'required|date',
            'status' => 'required|in:normal,abnormal,critical,pending',
        ]);
        
        // Check if the patient is a patient
        $patient = User::findOrFail($validated['patient_id']);
        if ($patient->role !== 'patient') {
            return response()->json([
                'message' => 'The selected user is not a patient'
            ], 422);
        }
        
        // Handle file upload if provided
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('medical_records', 'public');
            $validated['file_url'] = $path;
        }
        
        $record = MedicalRecord::create($validated);
        
        return response()->json($record->load(['patient', 'provider.user', 'hospital']), 201);
    }

    /**
     * Display the specified medical record.
     */
    public function show(MedicalRecord $medicalRecord)
    {
        return response()->json($medicalRecord->load(['patient', 'provider.user', 'hospital']));
    }

    /**
     * Update the specified medical record.
     */
    public function update(Request $request, MedicalRecord $medicalRecord)
    {
        $validated = $request->validate([
            'record_type' => 'sometimes|in:medical_report,lab_result,vaccination,prescription',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
            'provider_id' => 'nullable|exists:healthcare_providers,id',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'date' => 'sometimes|date',
            'status' => 'sometimes|in:normal,abnormal,critical,pending',
            'is_archived' => 'sometimes|boolean',
        ]);
        
        // Handle file upload if provided
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($medicalRecord->file_url) {
                Storage::disk('public')->delete($medicalRecord->file_url);
            }
            
            $path = $request->file('file')->store('medical_records', 'public');
            $validated['file_url'] = $path;
        }
        
        $medicalRecord->update($validated);
        
        return response()->json($medicalRecord);
    }

    /**
     * Remove the specified medical record.
     */
    public function destroy(MedicalRecord $medicalRecord)
    {
        // Delete file if exists
        if ($medicalRecord->file_url) {
            Storage::disk('public')->delete($medicalRecord->file_url);
        }
        
        $medicalRecord->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Archive a medical record.
     */
    public function archive(MedicalRecord $medicalRecord)
    {
        $medicalRecord->update([
            'is_archived' => true
        ]);
        
        return response()->json($medicalRecord);
    }
    
    /**
     * Unarchive a medical record.
     */
    public function unarchive(MedicalRecord $medicalRecord)
    {
        $medicalRecord->update([
            'is_archived' => false
        ]);
        
        return response()->json($medicalRecord);
    }
}