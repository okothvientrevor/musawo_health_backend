<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiseaseOutbreak extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'disease_id',
        'district_id',
        'start_date',
        'end_date',
        'status',
        'case_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the disease associated with this outbreak.
     */
    public function disease()
    {
        return $this->belongsTo(Disease::class);
    }

    /**
     * Get the district associated with this outbreak.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Scope a query to only include active outbreaks.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include outbreaks within a specific date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where('start_date', '>=', $startDate)
            ->where(function ($query) use ($endDate) {
                $query->where('end_date', '<=', $endDate)
                    ->orWhereNull('end_date');
            });
    }

    /**
     * Mark this outbreak as contained.
     */
    public function markAsContained()
    {
        $this->status = 'contained';
        $this->save();
    }

    /**
     * Mark this outbreak as resolved.
     */
    public function markAsResolved()
    {
        $this->status = 'resolved';
        $this->end_date = now();
        $this->save();
    }

    /**
     * Increment the case count for this outbreak.
     */
    public function incrementCaseCount($count = 1)
    {
        $this->case_count += $count;
        $this->save();
    }

    /**
     * Get the duration of the outbreak in days.
     */
    public function getDurationInDaysAttribute()
    {
        if ($this->start_date) {
            $endDate = $this->end_date ?? now();
            return $this->start_date->diffInDays($endDate);
        }
        return null;
    }
}