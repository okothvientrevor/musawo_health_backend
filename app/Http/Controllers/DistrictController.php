<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    /**
     * Display a listing of districts.
     */
    public function index(Request $request)
    {
        $districts = District::when($request->region, function ($query) use ($request) {
                return $query->where('region', $request->region);
            })
            ->when($request->search, function ($query) use ($request) {
                return $query->where('name', 'like', "%{$request->search}%");
            })
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);
            
        return response()->json($districts);
    }

    /**
     * Store a newly created district.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:districts,name',
            'region' => 'required|string|max:255',
        ]);
        
        $district = District::create($validated);
        
        return response()->json($district, 201);
    }

    /**
     * Display the specified district.
     */
    public function show(District $district)
    {
        return response()->json($district);
    }

    /**
     * Update the specified district.
     */
    public function update(Request $request, District $district)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:districts,name,'.$district->id,
            'region' => 'sometimes|string|max:255',
        ]);
        
        $district->update($validated);
        
        return response()->json($district);
    }

    /**
     * Remove the specified district.
     */
    public function destroy(District $district)
    {
        // Check if district has associated entities
        if ($district->hospitals()->count() > 0 || 
            $district->healthcareProviders()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete district with associated hospitals or healthcare providers'
            ], 422);
        }
        
        $district->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Get districts by region.
     */
    public function getByRegion($region)
    {
        $districts = District::where('region', $region)
            ->orderBy('name')
            ->get();
            
        return response()->json($districts);
    }
    
    /**
     * Get all regions.
     */
    public function getAllRegions()
    {
        $regions = District::select('region')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');
            
        return response()->json($regions);
    }
}