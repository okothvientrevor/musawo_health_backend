<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'national_id',
        'date_of_birth',
        'gender',
        'blood_type',
        'role',
        'profile_image',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
    ];

    /**
     * Get the patient record associated with the user.
     */
    public function patientRecord()
    {
        return $this->hasOne(PatientRecord::class);
    }

    /**
     * Get the healthcare provider record associated with the user.
     */
    public function healthcareProvider()
    {
        return $this->hasOne(HealthcareProvider::class);
    }

    /**
     * Get the appointments for the user.
     */
    public function patientAppointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    /**
     * Get the medical records for the user.
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'patient_id');
    }

    /**
     * Get the lab requests for the user.
     */
    public function labRequests()
    {
        return $this->hasMany(LabRequest::class, 'patient_id');
    }

    /**
     * Get the lab results for the lab technician.
     */
    public function labResults()
    {
        return $this->hasMany(LabResult::class, 'technician_id');
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the full name attribute.
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if user is a doctor.
     */
    public function isDoctor()
    {
        return $this->role === 'doctor';
    }

    /**
     * Check if user is a patient.
     */
    public function isPatient()
    {
        return $this->role === 'patient';
    }

    /**
     * Check if user is a nurse.
     */
    public function isNurse()
    {
        return $this->role === 'nurse';
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a lab technician.
     */
    public function isLabTechnician()
    {
        return $this->role === 'lab_technician';
    }
}