<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laboratory extends Model
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
        'operating_hours',
        'available_tests',
        'turnaround_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'operating_hours' => 'json',
        'available_tests' => 'json',
    ];

    /**
     * Get the district that contains the laboratory.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get the lab requests for the laboratory.
     */
    public function labRequests()
    {
        return $this->hasMany(LabRequest::class);
    }

    /**
     * Check if a specific test is available at this laboratory.
     */
    public function hasTest($testName)
    {
        $availableTests = $this->available_tests ?? [];
        return in_array($testName, $availableTests);
    }

    /**
     * Get all lab requests with a specific status.
     */
    public function getRequestsByStatus($status)
    {
        return $this->labRequests()->where('status', $status)->get();
    }

    /**
     * Scope a query to only include laboratories in a specific district.
     */
    public function scopeInDistrict($query, $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    /**
     * Scope a query to filter laboratories that offer a specific test.
     */
    public function scopeOfferingTest($query, $testName)
    {
        return $query->whereJsonContains('available_tests', $testName);
    }
}