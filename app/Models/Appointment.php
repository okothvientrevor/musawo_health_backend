<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'provider_id',
        'appointment_date',
        'status',
        'type',
        'reason',
        'notes',
        'fee_amount',
        'payment_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'appointment_date' => 'datetime',
        'fee_amount' => 'decimal:2',
    ];

    /**
     * Get the patient that owns the appointment.
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the healthcare provider for this appointment.
     */
    public function provider()
    {
        return $this->belongsTo(HealthcareProvider::class, 'provider_id');
    }

    /**
     * Get the consultation associated with this appointment.
     */
    public function consultation()
    {
        return $this->hasOne(Consultation::class);
    }

    /**
     * Scope a query to only include upcoming appointments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now())
            ->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include past appointments.
     */
    public function scopePast($query)
    {
        return $query->where('appointment_date', '<', now())
            ->orWhere('status', 'completed')
            ->orWhere('status', 'cancelled')
            ->orWhere('status', 'no_show');
    }

    /**
     * Scope a query to only include appointments of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include appointments with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include appointments with a specific payment status.
     */
    public function scopeWithPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Cancel this appointment.
     */
    public function cancel()
    {
        $this->status = 'cancelled';
        $this->save();
    }

    /**
     * Mark this appointment as completed.
     */
    public function complete()
    {
        $this->status = 'completed';
        $this->save();
    }

    /**
     * Mark this appointment as no-show.
     */
    public function markNoShow()
    {
        $this->status = 'no_show';
        $this->save();
    }
}