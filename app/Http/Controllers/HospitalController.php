<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use Illuminate\Http\Request;

class HospitalController extends Controller
{
    /**
     * Display a listing of hospitals.
     */
    public function index(Request $request)
    {
        $hospitals = Hospital::with('district')
            ->when($request->district_id, function ($query) use ($request) {
                return $query->where('district_id', $request->district_id);
            })
            ->when($request->has_emergency, function ($query) {
                return $query->where('has_emergency', true);
            })
            ->when($request->search, function ($query) use ($request) {
                return $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('address', 'like', "%{$request->search}%");
            })
            ->paginate($request->per_page ?? 15);
            
        return response()->json($hospitals);
    }

    /**
     * Store a newly created hospital.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'district_id' => 'required|exists:districts,id',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'services' => 'nullable|json',
            'has_emergency' => 'boolean',
            'coordinates' => 'nullable|string',
        ]);
        
        $hospital = Hospital::create($validated);
        
        return response()->json($hospital->load('district'), 201);
    }

    /**
     * Display the specified hospital.
     */
    public function show(Hospital $hospital)
    {
        return response()->json($hospital->load('district'));
    }

    /**
     * Update the specified hospital.
     */
    public function update(Request $request, Hospital $hospital)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'district_id' => 'sometimes|exists:districts,id',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'services' => 'nullable|json',
            'has_emergency' => 'boolean',
            'coordinates' => 'nullable|string',
        ]);
        
        $hospital->update($validated);
        
        return response()->json($hospital);
    }

    /**
     * Remove the specified hospital.
     */
    public function destroy(Hospital $hospital)
    {
        $hospital->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Get nearby hospitals based on location.
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'nullable|numeric|default:10', // in kilometers
            'has_emergency' => 'nullable|boolean',
        ]);
        
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 10;
        
        // MySQL spatial query to find nearby hospitals
        $hospitals = Hospital::selectRaw("
                hospitals.*,
                ST_Distance_Sphere(
                    point(?, ?),
                    coordinates
                ) / 1000 as distance_km", [$longitude, $latitude])
            ->whereRaw("ST_Distance_Sphere(
                    point(?, ?),
                    coordinates
                ) / 1000 <= ?", [$longitude, $latitude, $radius])
            ->when($request->has('has_emergency') && $request->has_emergency, function ($query) {
                return $query->where('has_emergency', true);
            })
            ->with('district')
            ->orderBy('distance_km')
            ->paginate($request->per_page ?? 15);
            
        return response()->json($hospitals);
    }
}