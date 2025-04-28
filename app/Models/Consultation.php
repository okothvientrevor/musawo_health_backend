<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_id',
        'start_time',
        'end_time',
        'symptoms',
        'diagnosis',
        'treatment_plan',
        'prescription',
        'follow_up_required',
        'follow_up_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'prescription' => 'json',
        'follow_up_required' => 'boolean',
        'follow_up_date' => 'date',
    ];

    /**
     * Get the appointment that owns the consultation.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the transcript for the consultation.
     */
    public function transcript()
    {
        return $this->hasOne(ConsultationTranscript::class);
    }

    /**
     * Get the patient through the appointment.
     */
    public function patient()
    {
        return $this->appointment->patient();
    }

    /**
     * Get the healthcare provider through the appointment.
     */
    public function provider()
    {
        return $this->appointment->provider();
    }

    /**
     * Start the consultation.
     */
    public function start()
    {
        $this->start_time = now();
        $this->save();
    }

    /**
     * End the consultation.
     */
    public function end()
    {
        $this->end_time = now();
        $this->save();
    }

    /**
     * Calculate the duration of the consultation in minutes.
     */
    public function getDurationInMinutesAttribute()
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->diffInMinutes($this->end_time);
        }
        return null;
    }

    /**
     * Add prescription item to the consultation.
     */
    public function addPrescriptionItem($medicine, $dosage, $instructions, $duration)
    {
        $prescription = $this->prescription ?? [];
        $prescription[] = [
            'medicine' => $medicine,
            'dosage' => $dosage,
            'instructions' => $instructions,
            'duration' => $duration,
            'prescribed_at' => now()->toDateString()
        ];
        
        $this->prescription = $prescription;
        $this->save();
    }
}