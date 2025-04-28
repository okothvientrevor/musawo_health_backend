<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationTranscript extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'consultation_id',
        'transcript_text',
        'ai_summary',
        'recording_url',
    ];

    /**
     * Get the consultation that owns the transcript.
     */
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Generate AI summary for the transcript.
     */
    public function generateAiSummary()
    {
        // Logic to generate AI summary would be implemented here
        // This is just a placeholder for the functionality
        
        $summary = "AI-generated summary of the consultation transcript";
        $this->ai_summary = $summary;
        $this->save();
        
        return $summary;
    }
}