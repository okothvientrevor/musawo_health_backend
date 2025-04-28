<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'region',
    ];

    /**
     * Get the hospitals in this district.
     */
    public function hospitals()
    {
        return $this->hasMany(Hospital::class);
    }

    /**
     * Get the healthcare providers in this district.
     */
    public function healthcareProviders()
    {
        return $this->hasMany(HealthcareProvider::class);
    }

    /**
     * Get the laboratories in this district.
     */
    public function laboratories()
    {
        return $this->hasMany(Laboratory::class);
    }

    /**
     * Get the disease outbreaks in this district.
     */
    public function diseaseOutbreaks()
    {
        return $this->hasMany(DiseaseOutbreak::class);
    }

    /**
     * Scope a query to only include districts in a specific region.
     */
    public function scopeInRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Get active disease outbreaks in this district.
     */
    public function activeOutbreaks()
    {
        return $this->diseaseOutbreaks()->where('status', 'active')->get();
    }

    /**
     * Get hospitals with emergency services in this district.
     */
    public function emergencyHospitals()
    {
        return $this->hospitals()->where('has_emergency', true)->get();
    }
}