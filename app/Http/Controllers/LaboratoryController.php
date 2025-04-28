<?php

namespace App\Http\Controllers;

use App\Models\Laboratory;
use Illuminate\Http\Request;

class LaboratoryController extends Controller
{
    /**
     * Display a listing of laboratories.
     */
    public function index(Request $request)
    {
        $laboratories = Laboratory::with('district')
            ->when($request->district_id, function ($query) use ($request) {
                return $query->where('district_id', $request->district_id);
            })
            ->when($request->search, function ($query) use ($request) {
                return $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('address', 'like', "%{$request->search}%");
            })
            ->paginate($request->per_page ?? 15);
            
        return response()->json($laboratories);
    }

    /**
     * Store a newly created laboratory.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'district_id' => 'required|exists:districts,id',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'operating_hours' => 'required|json',
            'available_tests' => 'required|json',
            'turnaround_time' => 'required|string|max:255',
        ]);
        
        $laboratory = Laboratory::create($validated);
        
        return response()->json($laboratory->load('district'), 201);
    }

    /**
     * Display the specified laboratory.
     */
    public function show(Laboratory $laboratory)
    {
        return response()->json($laboratory->load('district'));
    }

    /**
     * Update the specified laboratory.
     */
    public function update(Request $request, Laboratory $laboratory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'district_id' => 'sometimes|exists:districts,id',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'operating_hours' => 'sometimes|json',
            'available_tests' => 'sometimes|json',
            'turnaround_time' => 'sometimes|string|max:255',
        ]);
        
        $laboratory->update($validated);
        
        return response()->json($laboratory);
    }

    /**
     * Remove the specified laboratory.
     */
    public function destroy(Laboratory $laboratory)
    {
        $laboratory->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Get labs by test type.
     */
    public function getLabsByTest(Request $request)
    {
        $request->validate([
            'test_name' => 'required|string',
        ]);
        
        $testName = $request->test_name;
        
        $laboratories = Laboratory::whereRaw("JSON_CONTAINS(available_tests, ?)", ['"' . $testName . '"'])
            ->with('district')
            ->paginate($request->per_page ?? 15);
            
        return response()->json($laboratories);
    }
}