<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'symptoms',
        'prevention',
        'treatment',
        'severity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'symptoms' => 'json',
        'prevention' => 'json',
    ];

    /**
     * Get the outbreaks for this disease.
     */
    public function outbreaks()
    {
        return $this->hasMany(DiseaseOutbreak::class);
    }

    /**
     * Scope a query to only include diseases with a specific severity.
     */
    public function scopeWithSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Get active outbreaks for this disease.
     */
    public function activeOutbreaks()
    {
        return $this->outbreaks()->where('status', 'active')->get();
    }

    /**
     * Check if a symptom belongs to this disease.
     */
    public function hasSymptom($symptom)
    {
        $symptoms = $this->symptoms ?? [];
        return in_array($symptom, $symptoms);
    }

    /**
     * Check if this disease has any active outbreaks.
     */
    public function hasActiveOutbreak()
    {
        return $this->outbreaks()->where('status', 'active')->exists();
    }
}