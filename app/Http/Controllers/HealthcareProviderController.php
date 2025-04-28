<?php

namespace App\Http\Controllers;

use App\Models\HealthcareProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthcareProviderController extends Controller
{
    /**
     * Display a listing of healthcare providers.
     */
    public function index(Request $request)
    {
        $providers = HealthcareProvider::with(['user', 'district', 'hospital'])
            ->when($request->specialty, function ($query) use ($request) {
                return $query->where('specialty', $request->specialty);
            })
            ->when($request->district_id, function ($query) use ($request) {
                return $query->where('district_id', $request->district_id);
            })
            ->when($request->hospital_id, function ($query) use ($request) {
                return $query->where('hospital_id', $request->hospital_id);
            })
            ->when($request->supports_video, function ($query) {
                return $query->where('supports_video', true);
            })
            ->when($request->min_rating, function ($query) use ($request) {
                return $query->where('rating', '>=', $request->min_rating);
            })
            ->when($request->search, function ($query) use ($request) {
                return $query->whereHas('user', function ($q) use ($request) {
                    $q->where('first_name', 'like', "%{$request->search}%")
                        ->orWhere('last_name', 'like', "%{$request->search}%");
                });
            })
            ->paginate($request->per_page ?? 15);
        
        return response()->json($providers);
    }

    /**
     * Store a newly created healthcare provider.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'specialty' => 'required|string|max:255',
            'license_number' => 'required|string|unique:healthcare_providers,license_number',
            'years_experience' => 'required|integer|min:0',
            'bio' => 'nullable|string',
            'education' => 'nullable|string',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'consultation_fee' => 'required|numeric|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'supports_video' => 'boolean',
            'district_id' => 'required|exists:districts,id',
            'location_coordinates' => 'nullable|string',
        ]);
        
        // Verify user has proper role
        $user = User::findOrFail($validated['user_id']);
        if (!in_array($user->role, ['doctor', 'nurse'])) {
            return response()->json([
                'message' => 'The user must have role of doctor or nurse to be a healthcare provider'
            ], 422);
        }
        
        $provider = HealthcareProvider::create($validated);
        
        return response()->json($provider, 201);
    }

    /**
     * Display the specified healthcare provider.
     */
    public function show(HealthcareProvider $healthcareProvider)
    {
        return response()->json($healthcareProvider->load(['user', 'district', 'hospital']));
    }

    /**
     * Update the specified healthcare provider.
     */
    public function update(Request $request, HealthcareProvider $healthcareProvider)
    {
        $validated = $request->validate([
            'specialty' => 'sometimes|string|max:255',
            'license_number' => 'sometimes|string|unique:healthcare_providers,license_number,'.$healthcareProvider->id,
            'years_experience' => 'sometimes|integer|min:0',
            'bio' => 'nullable|string',
            'education' => 'nullable|string',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'consultation_fee' => 'sometimes|numeric|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'supports_video' => 'boolean',
            'district_id' => 'sometimes|exists:districts,id',
            'location_coordinates' => 'nullable|string',
        ]);
        
        $healthcareProvider->update($validated);
        
        return response()->json($healthcareProvider);
    }

    /**
     * Remove the specified healthcare provider.
     */
    public function destroy(HealthcareProvider $healthcareProvider)
    {
        $healthcareProvider->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Get nearby healthcare providers based on location.
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'nullable|numeric|default:10', // in kilometers
            'specialty' => 'nullable|string',
        ]);
        
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 10;
        
        // MySQL spatial query to find nearby providers
        $providers = HealthcareProvider::selectRaw("
                healthcare_providers.*,
                ST_Distance_Sphere(
                    point(?, ?),
                    location_coordinates
                ) / 1000 as distance_km", [$longitude, $latitude])
            ->whereRaw("ST_Distance_Sphere(
                    point(?, ?),
                    location_coordinates
                ) / 1000 <= ?", [$longitude, $latitude, $radius])
            ->when($request->specialty, function ($query) use ($request) {
                return $query->where('specialty', $request->specialty);
            })
            ->with(['user', 'district', 'hospital'])
            ->orderBy('distance_km')
            ->paginate($request->per_page ?? 15);
            
        return response()->json($providers);
    }
}