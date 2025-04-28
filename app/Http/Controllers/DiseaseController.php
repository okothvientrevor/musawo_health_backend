<?php

namespace App\Http\Controllers;

use App\Models\Disease;
use Illuminate\Http\Request;

class DiseaseController extends Controller
{
    /**
     * Display a listing of diseases.
     */
    public function index(Request $request)
    {
        $diseases = Disease::when($request->severity, function ($query) use ($request) {
                return $query->where('severity', $request->severity);
            })
            ->when($request->search, function ($query) use ($request) {
                return $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            })
            ->paginate($request->per_page ?? 15);
            
        return response()->json($diseases);
    }

    /**
     * Store a newly created disease.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:diseases,name',
            'description' => 'required|string',
            'symptoms' => 'required|json',
            'prevention' => 'required|json',
            'treatment' => 'required|string',
            'severity' => 'required|in:low,medium,high',
        ]);
        
        $disease = Disease::create($validated);
        
        return response()->json($disease, 201);
    }

    /**
     * Display the specified disease.
     */
    public function show(Disease $disease)
    {
        return response()->json($disease);
    }

    /**
     * Update the specified disease.
     */
    public function update(Request $request, Disease $disease)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:diseases,name,'.$disease->id,
            'description' => 'sometimes|string',
            'symptoms' => 'sometimes|json',
            'prevention' => 'sometimes|json',
            'treatment' => 'sometimes|string',
            'severity' => 'sometimes|in:low,medium,high',
        ]);
        
        $disease->update($validated);
        
        return response()->json($disease);
    }

    /**
     * Remove the specified disease.
     */
    public function destroy(Disease $disease)
    {
        // Check if disease has outbreaks
        if ($disease->outbreaks()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete disease with associated outbreaks'
            ], 422);
        }
        
        $disease->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Search diseases by symptom.
     */
    public function searchBySymptom(Request $request)
    {
        $request->validate([
            'symptom' => 'required|string',
        ]);
        
        $symptom = $request->symptom;
        
        $diseases = Disease::whereRaw("JSON_CONTAINS(symptoms, ?)", ['"' . $symptom . '"'])
            ->paginate($request->per_page ?? 15);
            
        return response()->json($diseases);
    }
}