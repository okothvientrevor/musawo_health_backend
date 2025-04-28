<?php

namespace App\Http\Controllers;

use App\Models\ConsultationTranscript;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConsultationTranscriptController extends Controller
{
    /**
     * Display a listing of transcripts.
     */
    public function index(Request $request)
    {
        $transcripts = ConsultationTranscript::with('consultation.appointment')
            ->when($request->consultation_id, function ($query) use ($request) {
                return $query->where('consultation_id', $request->consultation_id);
            })
            ->paginate($request->per_page ?? 15);
            
        return response()->json($transcripts);
    }

    /**
     * Store a newly created transcript.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'consultation_id' => 'required|exists:consultations,id',
            'transcript_text' => 'required|string',
            'ai_summary' => 'nullable|string',
            'recording' => 'nullable|file|mimes:mp3,wav,ogg|max:10240', // Max 10MB
        ]);
        
        // Check if transcript already exists for this consultation
        $existingTranscript = ConsultationTranscript::where('consultation_id', $validated['consultation_id'])->first();
        if ($existingTranscript) {
            return response()->json([
                'message' => 'A transcript already exists for this consultation',
                'transcript' => $existingTranscript
            ], 422);
        }
        
        // Handle recording upload if provided
        if ($request->hasFile('recording')) {
            $path = $request->file('recording')->store('consultation_recordings', 'public');
            $validated['recording_url'] = $path;
        }
        
        $transcript = ConsultationTranscript::create($validated);
        
        return response()->json($transcript, 201);
    }

    /**
     * Display the specified transcript.
     */
    public function show(ConsultationTranscript $consultationTranscript)
    {
        return response()->json($consultationTranscript->load('consultation.appointment'));
    }

    /**
     * Update the specified transcript.
     */
    public function update(Request $request, ConsultationTranscript $consultationTranscript)
    {
        $validated = $request->validate([
            'transcript_text' => 'sometimes|string',
            'ai_summary' => 'nullable|string',
            'recording' => 'nullable|file|mimes:mp3,wav,ogg|max:10240', // Max 10MB
        ]);
        
        // Handle recording upload if provided
        if ($request->hasFile('recording')) {
            // Delete old recording if exists
            if ($consultationTranscript->recording_url) {
                Storage::disk('public')->delete($consultationTranscript->recording_url);
            }
            
            $path = $request->file('recording')->store('consultation_recordings', 'public');
            $validated['recording_url'] = $path;
        }
        
        $consultationTranscript->update($validated);
        
        return response()->json($consultationTranscript);
    }

    /**
     * Remove the specified transcript.
     */
    public function destroy(ConsultationTranscript $consultationTranscript)
    {
        // Delete recording if exists
        if ($consultationTranscript->recording_url) {
            Storage::disk('public')->delete($consultationTranscript->recording_url);
        }
        
        $consultationTranscript->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Generate AI summary for transcript.
     */
    public function generateSummary(ConsultationTranscript $consultationTranscript)
    {
        // This would integrate with an AI service in a real application
        // For now, we'll just create a mock summary
        $transcriptText = $consultationTranscript->transcript_text;
        $summary = "This is an AI-generated summary of the consultation. The patient discussed their symptoms and the healthcare provider made recommendations for treatment.";
        
        $consultationTranscript->update([
            'ai_summary' => $summary
        ]);
        
        return response()->json($consultationTranscript);
    }
}