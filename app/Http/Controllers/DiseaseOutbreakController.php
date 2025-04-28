<?php

namespace App\Http\Controllers;

use App\Models\DiseaseOutbreak;
use Illuminate\Http\Request;

class DiseaseOutbreakController extends Controller
{
    /**
     * Display a listing of disease outbreaks.
     */
    public function index(Request $request)
    {
        $outbreaks = DiseaseOutbreak::with(['disease', 'district'])
            ->when($request->disease_id, function ($query) use ($request) {
                return $query->where('disease_id', $request->disease_id);
            })
            ->when($request->district_id, function ($query) use ($request) {
                return $query->where('district_id', $request->district_id);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->from_date && $request->to_date, function ($query) use ($request) {
                return $query->whereBetween('start_date', [$request->from_date, $request->to_date]);
            })
            ->orderBy('start_date', 'desc')
            ->paginate($request->per_page ?? 15);
            
        return response()->json($outbreaks);
    }

    /**
     * Store a newly created disease outbreak.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'disease_id' => 'required|exists:diseases,id',
            'district_id' => 'required|exists:districts,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,contained,resolved',
            'case_count' => 'required|integer|min:1',
        ]);
        
        $outbreak = DiseaseOutbreak::create($validated);
        
        return response()->json($outbreak->load(['disease', 'district']), 201);
    }

    /**
     * Display the specified disease outbreak.
     */
    public function show(DiseaseOutbreak $diseaseOutbreak)
    {
        return response()->json($diseaseOutbreak->load(['disease', 'district']));
    }

    /**
     * Update the specified disease outbreak.
     */
    public function update(Request $request, DiseaseOutbreak $diseaseOutbreak)
    {
        $validated = $request->validate([
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|in:active,contained,resolved',
            'case_count' => 'sometimes|integer|min:1',
        ]);
        
        // If status is changed to resolved, set end date if not already set
        if (isset($validated['status']) && $validated['status'] === 'resolved' && !isset($validated['end_date']) && !$diseaseOutbreak->end_date) {
            $validated['end_date'] = now();
        }
        
        $diseaseOutbreak->update($validated);
        
        return response()->json($diseaseOutbreak);
    }

    /**
     * Remove the specified disease outbreak.
     */
    public function destroy(DiseaseOutbreak $diseaseOutbreak)
    {
        $diseaseOutbreak->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Get active outbreaks.
     */
    public function getActiveOutbreaks()
    {
        $activeOutbreaks = DiseaseOutbreak::with(['disease', 'district'])
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->get();
            
        return response()->json($activeOutbreaks);
    }
    
    /**
     * Get outbreak stats.
     */
    public function getStats(Request $request)
    {
        $year = $request->year ?? date('Y');
        
        // Get outbreaks by disease
        $byDisease = DiseaseOutbreak::selectRaw('disease_id, SUM(case_count) as total_cases')
            ->whereYear('start_date', $year)
            ->groupBy('disease_id')
            ->with('disease:id,name')
            ->get();
            
        // Get outbreaks by district
        $byDistrict = DiseaseOutbreak::selectRaw('district_id, SUM(case_count) as total_cases')
            ->whereYear('start_date', $year)
            ->groupBy('district_id')
            ->with('district:id,name')
            ->get();
            
        // Get outbreaks by month
        $byMonth = DiseaseOutbreak::selectRaw('MONTH(start_date) as month, SUM(case_count) as total_cases')
            ->whereYear('start_date', $year)
            ->groupBy('month')
            ->get();
            
        return response()->json([
            'year' => (int) $year,
            'total_outbreaks' => DiseaseOutbreak::whereYear('start_date', $year)->count(),
            'total_cases' => DiseaseOutbreak::whereYear('start_date', $year)->sum('case_count'),
            'active_outbreaks' => DiseaseOutbreak::whereYear('start_date', $year)
                ->where('status', 'active')
                ->count(),
            'by_disease' => $byDisease,
            'by_district' => $byDistrict,
            'by_month' => $byMonth
        ]);
    }
}