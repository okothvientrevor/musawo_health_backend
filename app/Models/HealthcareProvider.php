<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthcareProvider extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'specialty',
        'license_number',
        'years_experience',
        'bio',
        'education',
        'hospital_id',
        'consultation_fee',
        'rating',
        'supports_video',
        'district_id',
        'location_coordinates',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'consultation_fee' => 'decimal:2',
        'rating' => 'decimal:1',
        'supports_video' => 'boolean',
        'location_coordinates' => 'array',
    ];

    /**
     * Get the user that owns the healthcare provider record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the hospital that employs the healthcare provider.
     */
    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * Get the district where the healthcare provider is located.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get the appointments for the healthcare provider.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'provider_id');
    }

    /**
     * Get the medical records created by the healthcare provider.
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'provider_id');
    }

    /**
     * Get the lab requests made by the healthcare provider.
     */
    public function labRequests()
    {
        return $this->hasMany(LabRequest::class, 'provider_id');
    }

    /**
     * Scope a query to only include providers of a specific specialty.
     */
    public function scopeSpecialty($query, $specialty)
    {
        return $query->where('specialty', $specialty);
    }

    /**
     * Scope a query to only include providers in a specific district.
     */
    public function scopeInDistrict($query, $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    /**
     * Scope a query to only include providers supporting video consultations.
     */
    public function scopeVideoSupport($query)
    {
        return $query->where('supports_video', true);
    }

    /**
     * Scope a query to order providers by rating.
     */
    public function scopeOrderByRating($query, $direction = 'desc')
    {
        return $query->orderBy('rating', $direction);
    }
}