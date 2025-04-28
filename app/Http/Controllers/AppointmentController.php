<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\HealthcareProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $appointments = Appointment::with(['patient', 'provider.user'])
            ->when($request->patient_id, function ($query) use ($request) {
                return $query->where('patient_id', $request->patient_id);
            })
            ->when($request->provider_id, function ($query) use ($request) {
                return $query->where('provider_id', $request->provider_id);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->type, function ($query) use ($request) {
                return $query->where('type', $request->type);
            })
            ->when($request->date, function ($query) use ($request) {
                return $query->whereDate('appointment_date', $request->date);
            })
            ->when($request->from_date && $request->to_date, function ($query) use ($request) {
                return $query->whereBetween('appointment_date', [$request->from_date, $request->to_date]);
            })
            // Filter based on user role
            ->when($user && $user->role === 'patient', function ($query) use ($user) {
                return $query->where('patient_id', $user->id);
            })
            ->when($user && ($user->role === 'doctor' || $user->role === 'nurse'), function ($query) use ($user) {
                $provider = HealthcareProvider::where('user_id', $user->id)->first();
                if ($provider) {
                    return $query->where('provider_id', $provider->id);
                }
            })
            ->orderBy('appointment_date')
            ->paginate($request->per_page ?? 15);
            
        return response()->json($appointments);
    }

    /**
     * Store a newly created appointment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'provider_id' => 'required|exists:healthcare_providers,id',
            'appointment_date' => 'required|date|after:now',
            'status' => 'required|in:scheduled,completed,cancelled,no_show',
            'type' => 'required|in:video,audio,in_person',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'fee_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid,refunded',
        ]);
        
        // Check if the patient is a patient
        $patient = User::findOrFail($validated['patient_id']);
        if ($patient->role !== 'patient') {
            return response()->json([
                'message' => 'The selected user is not a patient'
            ], 422);
        }
        
        // Check for time conflicts
        $conflictingAppointment = Appointment::where('provider_id', $validated['provider_id'])
            ->where('appointment_date', $validated['appointment_date'])
            ->where('status', '!=', 'cancelled')
            ->first();
            
        if ($conflictingAppointment) {
            return response()->json([
                'message' => 'The healthcare provider already has an appointment at this time'
            ], 422);
        }
        
        $appointment = Appointment::create($validated);
        
        return response()->json($appointment->load(['patient', 'provider.user']), 201);
    }

    /**
     * Display the specified appointment.
     */
    public function show(Appointment $appointment)
    {
        return response()->json($appointment->load(['patient', 'provider.user']));
    }

    /**
     * Update the specified appointment.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'appointment_date' => 'sometimes|date|after:now',
            'status' => 'sometimes|in:scheduled,completed,cancelled,no_show',
            'type' => 'sometimes|in:video,audio,in_person',
            'reason' => 'sometimes|string',
            'notes' => 'nullable|string',
            'fee_amount' => 'sometimes|numeric|min:0',
            'payment_status' => 'sometimes|in:pending,paid,refunded',
        ]);
        
        // Check for time conflicts if appointment date is being changed
        if (isset($validated['appointment_date']) && $validated['appointment_date'] != $appointment->appointment_date) {
            $conflictingAppointment = Appointment::where('provider_id', $appointment->provider_id)
                ->where('id', '!=', $appointment->id)
                ->where('appointment_date', $validated['appointment_date'])
                ->where('status', '!=', 'cancelled')
                ->first();
                
            if ($conflictingAppointment) {
                return response()->json([
                    'message' => 'The healthcare provider already has an appointment at this time'
                ], 422);
            }
        }
        
        $appointment->update($validated);
        
        return response()->json($appointment->load(['patient', 'provider.user']));
    }

    /**
     * Remove the specified appointment.
     */
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Get available time slots for a provider.
     */
    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:healthcare_providers,id',
            'date' => 'required|date|after_or_equal:today',
        ]);
        
        $providerId = $request->provider_id;
        $date = $request->date;
        
        // Get the provider's booked appointments for that day
        $bookedSlots = Appointment::where('provider_id', $providerId)
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->pluck('appointment_date')
            ->map(function ($dateTime) {
                return date('H:i', strtotime($dateTime));
            })
            ->toArray();
        
        // Generate time slots (assuming 30-minute appointments from 8 AM to 5 PM)
        $availableSlots = [];
        $startTime = strtotime('08:00');
        $endTime = strtotime('17:00');
        $interval = 30 * 60; // 30 minutes in seconds
        
        for ($time = $startTime; $time <= $endTime; $time += $interval) {
            $slotTime = date('H:i', $time);
            if (!in_array($slotTime, $bookedSlots)) {
                $availableSlots[] = $slotTime;
            }
        }
        
        return response()->json([
            'date' => $date,
            'provider_id' => $providerId,
            'available_slots' => $availableSlots
        ]);
    }
}
