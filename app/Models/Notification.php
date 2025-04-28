<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'read_at',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
        'data' => 'json',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope a query to only include notifications of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead()
    {
        $this->read_at = now();
        $this->save();
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread()
    {
        $this->read_at = null;
        $this->save();
    }

    /**
     * Check if the notification is read.
     */
    public function isRead()
    {
        return $this->read_at !== null;
    }

    /**
     * Create an appointment notification for a user.
     */
    public static function createAppointmentNotification($user, $appointment, $message)
    {
        return self::create([
            'user_id' => $user->id,
            'title' => 'Appointment Update',
            'message' => $message,
            'type' => 'appointment',
            'data' => [
                'appointment_id' => $appointment->id,
                'appointment_date' => $appointment->appointment_date,
                'provider_name' => $appointment->provider->user->full_name ?? 'Unknown',
            ],
        ]);
    }

    /**
     * Create a lab result notification for a user.
     */
    public static function createLabResultNotification($user, $labResult)
    {
        return self::create([
            'user_id' => $user->id,
            'title' => 'Lab Results Available',
            'message' => 'Your lab results are now available. Please check your medical records.',
            'type' => 'lab_result',
            'data' => [
                'lab_request_id' => $labResult->lab_request_id,
                'result_date' => $labResult->result_date,
            ],
        ]);
    }

    /**
     * Create an outbreak notification for users in a district.
     */
    public static function createOutbreakNotification($districtId, $outbreak)
    {
        // Get all users in this district (could be based on their healthcare provider or other logic)
        $usersInDistrict = User::whereHas('healthcareProvider', function ($query) use ($districtId) {
            $query->where('district_id', $districtId);
        })->get();

        foreach ($usersInDistrict as $user) {
            self::create([
                'user_id' => $user->id,
                'title' => 'Disease Outbreak Alert',
                'message' => "There is an active {$outbreak->disease->name} outbreak in your area.",
                'type' => 'outbreak',
                'data' => [
                    'outbreak_id' => $outbreak->id,
                    'disease_name' => $outbreak->disease->name,
                    'district_name' => $outbreak->district->name,
                    'case_count' => $outbreak->case_count,
                ],
            ]);
        }
    }
}