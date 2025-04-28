<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lab_request_id',
        'results',
        'technician_id',
        'result_date',
        'file_url',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'results' => 'json',
        'result_date' => 'datetime',
    ];

    /**
     * Get the lab request that owns the result.
     */
    public function labRequest()
    {
        return $this->belongsTo(LabRequest::class);
    }

    /**
     * Get the technician who processed the lab result.
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Get the patient through the lab request.
     */
    public function patient()
    {
        return $this->labRequest->patient();
    }

    /**
     * Get the requesting provider through the lab request.
     */
    public function requestingProvider()
    {
        return $this->labRequest->provider();
    }

    /**
     * Get the laboratory through the lab request.
     */
    public function laboratory()
    {
        return $this->labRequest->laboratory();
    }

    /**
     * Add a test result to the results array.
     */
    public function addTestResult($testName, $value, $normalRange, $status)
    {
        $results = $this->results ?? [];
        $results[] = [
            'test_name' => $testName,
            'value' => $value,
            'normal_range' => $normalRange,
            'status' => $status,
        ];
        
        $this->results = $results;
        $this->save();
    }

    /**
     * Check if any results have abnormal or critical status.
     */
    public function hasAbnormalResults()
    {
        if (!$this->results) {
            return false;
        }
        
        foreach ($this->results as $result) {
            if (isset($result['status']) && ($result['status'] === 'abnormal' || $result['status'] === 'critical')) {
                return true;
            }
        }
        
        return false;
    }
}