<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hospital extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'district_id',
        'phone',
        'email',
        'website',
        'services',
        'has_emergency',
        'coordinates',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'services' => 'json',
        'has_emergency' => 'boolean',
        'coordinates' => 'array',
    ];

    /**
     * Get the district that contains the hospital.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get the healthcare providers working at this hospital.
     */
    public function healthcareProviders()
    {
        return $this->hasMany(HealthcareProvider::class);
    }

    /**
     * Get the medical records associated with this hospital.
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Scope a query to only include hospitals in a specific district.
     */
    public function scopeInDistrict($query, $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    /**
     * Scope a query to only include hospitals with emergency services.
     */
    public function scopeWithEmergency($query)
    {
        return $query->where('has_emergency', true);
    }

    /**
     * Scope a query to only include hospitals that offer a specific service.
     */
    public function scopeOfferingService($query, $service)
    {
        return $query->whereJsonContains('services', $service);
    }

    /**
     * Check if the hospital offers a specific service.
     */
    public function hasService($service)
    {
        $services = $this->services ?? [];
        return in_array($service, $services);
    }

    /**
     * Get all doctors working at this hospital.
     */
    public function doctors()
    {
        return $this->healthcareProviders()
            ->whereHas('user', function ($query) {
                $query->where('role', 'doctor');
            });
    }

    /**
     * Get all nurses working at this hospital.
     */
    public function nurses()
    {
        return $this->healthcareProviders()
            ->whereHas('user', function ($query) {
                $query->where('role', 'nurse');
            });
    }
}