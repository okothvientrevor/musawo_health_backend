<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabRequest extends Model
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
        'laboratory_id',
        'tests_requested',
        'urgency_level',
        'notes',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tests_requested' => 'json',
    ];

    /**
     * Get the patient that owns the lab request.
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the healthcare provider that created the lab request.
     */
    public function provider()
    {
        return $this->belongsTo(HealthcareProvider::class, 'provider_id');
    }

    /**
     * Get the laboratory that will process the lab request.
     */
    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
    }

    /**
     * Get the lab result for this request.
     */
    public function labResult()
    {
        return $this->hasOne(LabResult::class);
    }

    /**
     * Update the status of the lab request.
     */
    public function updateStatus($status)
    {
        $this->status = $status;
        $this->save();
    }

    /**
     * Scope a query to only include lab requests with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include lab requests with a specific urgency level.
     */
    public function scopeWithUrgency($query, $urgencyLevel)
    {
        return $query->where('urgency_level', $urgencyLevel);
    }

    /**
     * Scope a query to only include lab requests for a specific test.
     */
    public function scopeForTest($query, $testName)
    {
        return $query->whereJsonContains('tests_requested', $testName);
    }
}