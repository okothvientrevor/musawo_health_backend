<?php

namespace App\Http\Controllers;

use App\Models\LabRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabRequestController extends Controller
{
    /**
     * Display a listing of lab requests.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $labRequests = LabRequest::with(['patient', 'provider.user', 'laboratory'])
            ->when($request->patient_id, function ($query) use ($request) {
                return $query->where('patient_id', $request->patient_id);
            })
            ->when($request->provider_id, function ($query) use ($request) {
                return $query->where('provider_id', $request->provider_id);
            })
            ->when($request->laboratory_id, function ($query) use ($request) {
                return $query->where('laboratory_id', $request->laboratory_id);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->urgency_level, function ($query) use ($request) {
                return $query->where('urgency_level', $request->urgency_level);
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
            ->when($user && $user->role === 'lab_technician', function ($query) use ($user) {
                // Lab technician can see all requests for their laboratory
                // This would require storing the technician's laboratory_id in the user model or a separate table
                // For simplicity, we're allowing lab technicians to see all lab requests
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);
            
        return response()->json($labRequests);
    }

    /**
     * Store a newly created lab request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'provider_id' => 'nullable|exists:healthcare_providers,id',
            'laboratory_id' => 'required|exists:laboratories,id',
            'tests_requested' => 'required|json',
            'urgency_level' => 'required|in:routine,urgent,emergency',
            'notes' => 'nullable|string',
        ]);
        
        // Check if the patient is a patient
        $patient = User::findOrFail($validated['patient_id']);
        if ($patient->role !== 'patient') {
            return response()->json([
                'message' => 'The selected user is not a patient'
            ], 422);
        }
        
        // Set initial status
        $validated['status'] = 'requested';
        
        $labRequest = LabRequest::create($validated);
        
        return response()->json($labRequest->load(['patient', 'provider.user', 'laboratory']), 201);
    }

    /**
     * Display the specified lab request.
     */
    public function show(LabRequest $labRequest)
    {
        return response()->json($labRequest->load(['patient', 'provider.user', 'laboratory']));
    }

    /**
     * Update the specified lab request.
     */
    public function update(Request $request, LabRequest $labRequest)
    {
        $validated = $request->validate([
            'laboratory_id' => 'sometimes|exists:laboratories,id',
            'tests_requested' => 'sometimes|json',
            'urgency_level' => 'sometimes|in:routine,urgent,emergency',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:requested,processing,completed,cancelled',
        ]);
        
        $labRequest->update($validated);
        
        return response()->json($labRequest);
    }

    /**
     * Remove the specified lab request.
     */
    public function destroy(LabRequest $labRequest)
    {
        // Check if request can be deleted (only if it's not completed or processing)
        if (in_array($labRequest->status, ['completed', 'processing'])) {
            return response()->json([
                'message' => 'Cannot delete a lab request that is already processing or completed'
            ], 422);
        }
        
        $labRequest->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Update the status of a lab request.
     */
    public function updateStatus(Request $request, LabRequest $labRequest)
    {
        $request->validate([
            'status' => 'required|in:requested,processing,completed,cancelled',
        ]);
        
        // Check if the status transition is valid
        $currentStatus = $labRequest->status;
        $newStatus = $request->status;
        
        $validTransitions = [
            'requested' => ['processing', 'cancelled'],
            'processing' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => []
        ];
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'message' => "Cannot change status from $currentStatus to $newStatus"
            ], 422);
        }
        
        $labRequest->update([
            'status' => $newStatus
        ]);
        
        return response()->json($labRequest);
    }
}