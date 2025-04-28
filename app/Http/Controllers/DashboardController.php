<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DiseaseOutbreak;
use App\Models\LabRequest;
use App\Models\User;
use App\Models\HealthcareProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard data.
     */
    public function adminDashboard()
    {
        // Total counts
        $userCount = User::count();
        $patientCount = User::where('role', 'patient')->count();
        $doctorCount = User::where('role', 'doctor')->count();
        $nurseCount = User::where('role', 'nurse')->count();
        
        // Appointments stats
        $totalAppointments = Appointment::count();
        $todayAppointments = Appointment::whereDate('appointment_date', today())->count();
        $pendingAppointments = Appointment::where('status', 'scheduled')->count();
        
        // Recent outbreaks
        $recentOutbreaks = DiseaseOutbreak::with(['disease', 'district'])
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->take(5)
            ->get();
            
        // User registrations over time
        $userRegistrations = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // District distribution of patients
        $patientsByDistrict = User::selectRaw('district_id, COUNT(*) as count')
            ->join('patient_records', 'users.id', '=', 'patient_records.user_id')
            ->where('role', 'patient')
            ->groupBy('district_id')
            ->with('district:id,name')
            ->take(10)
            ->get();
        
        return response()->json([
            'users' => [
                'total' => $userCount,
                'patients' => $patientCount,
                'doctors' => $doctorCount,
                'nurses' => $nurseCount
            ],
            'appointments' => [
                'total' => $totalAppointments,
                'today' => $todayAppointments,
                'pending' => $pendingAppointments
            ],
            'recent_outbreaks' => $recentOutbreaks,
            'user_registrations' => $userRegistrations,
            'patients_by_district' => $patientsByDistrict
        ]);
    }
    
    /**
     * Get doctor dashboard data.
     */
    public function doctorDashboard(Request $request)
    {
        $user = Auth::user();
        
        // Get healthcare provider record
        $provider = HealthcareProvider::where('user_id', $user->id)->firstOrFail();
        
        // Upcoming appointments
        $upcomingAppointments = Appointment::with('patient')
            ->where('provider_id', $provider->id)
            ->where('status', 'scheduled')
            ->whereDate('appointment_date', '>=', today())
            ->orderBy('appointment_date')
            ->take(5)
            ->get();
        
        // Today's appointments
        $todayAppointments = Appointment::with('patient')
            ->where('provider_id', $provider->id)
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_date')
            ->get();
            
        // Appointment statistics
        $appointmentStats = [
            'today' => Appointment::where('provider_id', $provider->id)
                ->whereDate('appointment_date', today())
                ->count(),
            'this_week' => Appointment::where('provider_id', $provider->id)
                ->whereBetween('appointment_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])
                ->count(),
            'this_month' => Appointment::where('provider_id', $provider->id)
                ->whereYear('appointment_date', now()->year)
                ->whereMonth('appointment_date', now()->month)
                ->count(),
            'pending' => Appointment::where('provider_id', $provider->id)
                ->where('status', 'scheduled')
                ->count()
        ];
        
        // Recent lab requests
        $recentLabRequests = LabRequest::with(['patient', 'laboratory'])
            ->where('provider_id', $provider->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Recent outbreak alerts in provider's district
        $recentOutbreaks = DiseaseOutbreak::with(['disease', 'district'])
            ->where('district_id', $provider->district_id)
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->take(3)
            ->get();
            
        // Appointment types distribution
        $appointmentTypes = Appointment::selectRaw('type, COUNT(*) as count')
            ->where('provider_id', $provider->id)
            ->groupBy('type')
            ->get();
            
        return response()->json([
            'provider' => $provider,
            'upcoming_appointments' => $upcomingAppointments,
            'today_appointments' => $todayAppointments,
            'appointment_stats' => $appointmentStats,
            'recent_lab_requests' => $recentLabRequests,
            'recent_outbreaks' => $recentOutbreaks,
            'appointment_types' => $appointmentTypes
        ]);
    }
    
    /**
     * Get patient dashboard data.
     */
    public function patientDashboard()
    {
        $user = Auth::user();
        
        // Upcoming appointments
        $upcomingAppointments = Appointment::with(['provider.user', 'provider.hospital'])
            ->where('patient_id', $user->id)
            ->where('status', 'scheduled')
            ->whereDate('appointment_date', '>=', today())
            ->orderBy('appointment_date')
            ->take(5)
            ->get();
            
        // Recent lab requests and results
        $recentLabRequests = LabRequest::with(['laboratory', 'labResult'])
            ->where('patient_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        // Recent medical records
        $recentMedicalRecords = DB::table('medical_records')
            ->where('patient_id', $user->id)
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();
            
        // Patient record
        $patientRecord = $user->patientRecord;
        
        // Nearby healthcare providers
        // This would require location data from the user's request
        // For now, just return providers from the user's district if available
        $nearbyProviders = null;
        if ($patientRecord && $patientRecord->district_id) {
            $nearbyProviders = HealthcareProvider::with(['user', 'hospital'])
                ->where('district_id', $patientRecord->district_id)
                ->take(5)
                ->get();
        }
        
        // Recent outbreaks in patient's district
        $recentOutbreaks = null;
        if ($patientRecord && $patientRecord->district_id) {
            $recentOutbreaks = DiseaseOutbreak::with(['disease', 'district'])
                ->where('district_id', $patientRecord->district_id)
                ->where('status', 'active')
                ->orderBy('start_date', 'desc')
                ->take(3)
                ->get();
        }
        
        return response()->json([
            'upcoming_appointments' => $upcomingAppointments,
            'recent_lab_requests' => $recentLabRequests,
            'recent_medical_records' => $recentMedicalRecords,
            'patient_record' => $patientRecord,
            'nearby_providers' => $nearbyProviders,
            'recent_outbreaks' => $recentOutbreaks
        ]);
    }
}