<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'allergies',
        'chronic_conditions',
        'current_medications',
        'blood_pressure_history',
        'weight_history',
        'height',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allergies' => 'json',
        'chronic_conditions' => 'json',
        'current_medications' => 'json',
        'blood_pressure_history' => 'json',
        'weight_history' => 'json',
        'height' => 'decimal:2',
    ];

    /**
     * Get the user that owns the patient record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Add a new allergy to the patient's record.
     *
     * @param string $allergy
     * @return void
     */
    public function addAllergy($allergy)
    {
        $allergies = $this->allergies ?? [];
        if (!in_array($allergy, $allergies)) {
            $allergies[] = $allergy;
            $this->allergies = $allergies;
            $this->save();
        }
    }

    /**
     * Add a chronic condition to the patient's record.
     *
     * @param string $condition
     * @return void
     */
    public function addChronicCondition($condition)
    {
        $conditions = $this->chronic_conditions ?? [];
        if (!in_array($condition, $conditions)) {
            $conditions[] = $condition;
            $this->chronic_conditions = $conditions;
            $this->save();
        }
    }

    /**
     * Add a medication to the patient's current medications.
     *
     * @param array $medication
     * @return void
     */
    public function addMedication($medication)
    {
        $medications = $this->current_medications ?? [];
        $medications[] = $medication;
        $this->current_medications = $medications;
        $this->save();
    }

    /**
     * Add a blood pressure reading to the history.
     *
     * @param array $reading
     * @return void
     */
    public function addBloodPressureReading($reading)
    {
        $history = $this->blood_pressure_history ?? [];
        $reading['date'] = now()->toDateString();
        $history[] = $reading;
        $this->blood_pressure_history = $history;
        $this->save();
    }

    /**
     * Add a weight record to the history.
     *
     * @param float $weight
     * @return void
     */
    public function addWeightRecord($weight)
    {
        $history = $this->weight_history ?? [];
        $history[] = [
            'weight' => $weight,
            'date' => now()->toDateString()
        ];
        $this->weight_history = $history;
        $this->save();
    }
}