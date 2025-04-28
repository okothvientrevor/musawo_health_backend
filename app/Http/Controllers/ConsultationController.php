<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultationController extends Controller
{
    /**
     * Display a listing of consultations.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $consultations = Consultation::with(['appointment.patient', 'appointment.provider.user'])
            ->when($request->appointment_id, function ($query) use ($request) {
                return $query->where('appointment_id', $request->appointment_id);
            })
            ->when($user && $user->role === 'patient', function ($query) use ($user) {
                return $query->whereHas('appointment', function ($q) use ($user) {
                    $q->where('patient_id', $user->id);
                });
            })
            ->when($user && ($user->role === 'doctor' || $user->role === 'nurse'), function ($query) use ($user) {
                return $query->whereHas('appointment.provider', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->paginate($request->per_page ?? 15);
            
        return response()->json($consultations);
    }

    /**
     * Store a newly created consultation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'start_time' => 'required|date',
            'symptoms' => 'required|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'prescription' => 'nullable|json',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'nullable|date|after:start_time',
        ]);
        
        // Check if consultation already exists for this appointment
        $existingConsultation = Consultation::where('appointment_id', $validated['appointment_id'])->first();
        if ($existingConsultation) {
            return response()->json([
                'message' => 'A consultation already exists for this appointment',
                'consultation' => $existingConsultation
            ], 422);
        }
        
        // Update appointment status to completed
        $appointment = Appointment::findOrFail($validated['appointment_id']);
        $appointment->update(['status' => 'completed']);
        
        $consultation = Consultation::create($validated);
        
        return response()->json($consultation->load(['appointment.patient', 'appointment.provider.user']), 201);
    }

    /**
     * Display the specified consultation.
     */
    public function show(Consultation $consultation)
    {
        return response()->json($consultation->load(['appointment.patient', 'appointment.provider.user']));
    }

    /**
     * Update the specified consultation.
     */
    public function update(Request $request, Consultation $consultation)
    {
        $validated = $request->validate([
            'end_time' => 'nullable|date|after:start_time',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'prescription' => 'nullable|json',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'nullable|date|after:start_time',
        ]);
        
        $consultation->update($validated);
        
        return response()->json($consultation);
    }

    /**
     * Remove the specified consultation.
     */
    public function destroy(Consultation $consultation)
    {
        // First check if there are any records that depend on this consultation
        if ($consultation->transcript) {
            return response()->json([
                'message' => 'Cannot delete consultation with associated transcript'
            ], 422);
        }
        
        $consultation->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * End an ongoing consultation.
     */
    public function endConsultation(Consultation $consultation)
    {
        if ($consultation->end_time) {
            return response()->json([
                'message' => 'Consultation is already ended'
            ], 422);
        }
        
        $consultation->update([
            'end_time' => now()
        ]);
        
        return response()->json($consultation);
    }
}
