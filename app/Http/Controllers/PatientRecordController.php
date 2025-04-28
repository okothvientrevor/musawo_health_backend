<?php

namespace App\Http\Controllers;

use App\Models\PatientRecord;
use App\Models\User;
use Illuminate\Http\Request;

class PatientRecordController extends Controller
{
    /**
     * Display a listing of patient records.
     */
    public function index(Request $request)
    {
        // This endpoint should be restricted to admin users
        $records = PatientRecord::with('user')
            ->when($request->search, function ($query) use ($request) {
                return $query->whereHas('user', function ($q) use ($request) {
                    $q->where('first_name', 'like', "%{$request->search}%")
                      ->orWhere('last_name', 'like', "%{$request->search}%")
                      ->orWhere('national_id', 'like', "%{$request->search}%");
                });
            })
            ->paginate($request->per_page ?? 15);
            
        return response()->json($records);
    }

    /**
     * Store a newly created patient record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'allergies' => 'nullable|json',
            'chronic_conditions' => 'nullable|json',
            'current_medications' => 'nullable|json',
            'blood_pressure_history' => 'nullable|json',
            'weight_history' => 'nullable|json',
            'height' => 'nullable|numeric',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);
        
        // Check if the user is a patient
        $user = User::findOrFail($validated['user_id']);
        if ($user->role !== 'patient') {
            return response()->json([
                'message' => 'Only patients can have patient records'
            ], 422);
        }
        
        // Check if record already exists
        $existingRecord = PatientRecord::where('user_id', $validated['user_id'])->first();
        if ($existingRecord) {
            return response()->json([
                'message' => 'Patient already has a record',
                'record' => $existingRecord
            ], 422);
        }
        
        $record = PatientRecord::create($validated);
        
        return response()->json($record, 201);
    }

    /**
     * Display the specified patient record.
     */
    public function show(PatientRecord $patientRecord)
    {
        return response()->json($patientRecord->load('user'));
    }

    /**
     * Get patient record by user ID.
     */
    public function getByUserId($userId)
    {
        $record = PatientRecord::where('user_id', $userId)->firstOrFail();
        return response()->json($record);
    }

    /**
     * Update the specified patient record.
     */
    public function update(Request $request, PatientRecord $patientRecord)
    {
        $validated = $request->validate([
            'allergies' => 'nullable|json',
            'chronic_conditions' => 'nullable|json',
            'current_medications' => 'nullable|json',
            'blood_pressure_history' => 'nullable|json',
            'weight_history' => 'nullable|json',
            'height' => 'nullable|numeric',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);
        
        $patientRecord->update($validated);
        
        return response()->json($patientRecord);
    }

    /**
     * Remove the specified patient record.
     */
    public function destroy(PatientRecord $patientRecord)
    {
        $patientRecord->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Add a new blood pressure reading.
     */
    public function addBloodPressure(Request $request, PatientRecord $patientRecord)
    {
        $validated = $request->validate([
            'systolic' => 'required|integer',
            'diastolic' => 'required|integer',
            'pulse' => 'required|integer',
            'recorded_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        
        $bpHistory = json_decode($patientRecord->blood_pressure_history ?? '[]', true);
        $bpHistory[] = $validated;
        
        $patientRecord->update([
            'blood_pressure_history' => json_encode($bpHistory)
        ]);
        
        return response()->json($patientRecord);
    }
    
    /**
     * Add a new weight measurement.
     */
    public function addWeight(Request $request, PatientRecord $patientRecord)
    {
        $validated = $request->validate([
            'weight' => 'required|numeric',
            'unit' => 'required|in:kg,lb',
            'recorded_at' => 'required|date',
        ]);
        
        $weightHistory = json_decode($patientRecord->weight_history ?? '[]', true);
        $weightHistory[] = $validated;
        
        $patientRecord->update([
            'weight_history' => json_encode($weightHistory)
        ]);
        
        return response()->json($patientRecord);
    }
}