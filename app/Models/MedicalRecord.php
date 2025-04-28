<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'record_type',
        'title',
        'description',
        'file_url',
        'provider_id',
        'hospital_id',
        'date',
        'status',
        'is_archived',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'is_archived' => 'boolean',
    ];

    /**
     * Get the patient that owns the medical record.
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the healthcare provider associated with the record.
     */
    public function provider()
    {
        return $this->belongsTo(HealthcareProvider::class, 'provider_id');
    }

    /**
     * Get the hospital associated with the record.
     */
    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * Scope a query to only include records of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('record_type', $type);
    }

    /**
     * Scope a query to only include records with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include archived or non-archived records.
     */
    public function scopeArchived($query, $archived = true)
    {
        return $query->where('is_archived', $archived);
    }

    /**
     * Archive this medical record.
     */
    public function archive()
    {
        $this->is_archived = true;
        $this->save();
    }

    /**
     * Unarchive this medical record.
     */
    public function unarchive()
    {
        $this->is_archived = false;
        $this->save();
    }
}