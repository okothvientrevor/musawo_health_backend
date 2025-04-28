<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ConsultationTranscriptController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiseaseController;
use App\Http\Controllers\DiseaseOutbreakController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\HealthcareProviderController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\LabRequestController;
use App\Http\Controllers\LabResultController;
use App\Http\Controllers\LaboratoryController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientRecordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/national-id', [AuthController::class, 'nationalIdLogin']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});

Route::get('/districts', [DistrictController::class, 'index']);
Route::get('/districts/regions', [DistrictController::class, 'getAllRegions']);
Route::get('/districts/region/{region}', [DistrictController::class, 'getByRegion']);
Route::get('/districts/{district}', [DistrictController::class, 'show']);

Route::get('/diseases', [DiseaseController::class, 'index']);
Route::get('/diseases/search-by-symptom', [DiseaseController::class, 'searchBySymptom']);
Route::get('/diseases/{disease}', [DiseaseController::class, 'show']);

Route::get('/disease-outbreaks', [DiseaseOutbreakController::class, 'index']);
Route::get('/disease-outbreaks/active', [DiseaseOutbreakController::class, 'getActiveOutbreaks']);
Route::get('/disease-outbreaks/stats', [DiseaseOutbreakController::class, 'getStats']);
Route::get('/disease-outbreaks/{diseaseOutbreak}', [DiseaseOutbreakController::class, 'show']);

Route::get('/hospitals', [HospitalController::class, 'index']);
Route::get('/hospitals/nearby', [HospitalController::class, 'nearby']);
Route::get('/hospitals/{hospital}', [HospitalController::class, 'show']);

Route::get('/laboratories', [LaboratoryController::class, 'index']);
Route::get('/laboratories/by-test', [LaboratoryController::class, 'getLabsByTest']);
Route::get('/laboratories/{laboratory}', [LaboratoryController::class, 'show']);

Route::get('/healthcare-providers', [HealthcareProviderController::class, 'index']);
Route::get('/healthcare-providers/nearby', [HealthcareProviderController::class, 'nearby']);
Route::get('/healthcare-providers/{healthcareProvider}', [HealthcareProviderController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    
    // User routes
    Route::apiResource('users', UserController::class);
    
    // Appointments
    Route::apiResource('appointments', AppointmentController::class);
    Route::get('/appointments/available-slots', [AppointmentController::class, 'getAvailableSlots']);
    
    // Consultations
    Route::apiResource('consultations', ConsultationController::class);
    Route::patch('/consultations/{consultation}/end', [ConsultationController::class, 'endConsultation']);
    
    // Consultation Transcripts
    Route::apiResource('consultation-transcripts', ConsultationTranscriptController::class);
    Route::post('/consultation-transcripts/{consultationTranscript}/generate-summary', [ConsultationTranscriptController::class, 'generateSummary']);
    
    // Patient Records
    Route::apiResource('patient-records', PatientRecordController::class);
    Route::get('/patient-records/user/{userId}', [PatientRecordController::class, 'getByUserId']);
    Route::post('/patient-records/{patientRecord}/blood-pressure', [PatientRecordController::class, 'addBloodPressure']);
    Route::post('/patient-records/{patientRecord}/weight', [PatientRecordController::class, 'addWeight']);
    
    // Medical Records
    Route::apiResource('medical-records', MedicalRecordController::class);
    Route::patch('/medical-records/{medicalRecord}/archive', [MedicalRecordController::class, 'archive']);
    Route::patch('/medical-records/{medicalRecord}/unarchive', [MedicalRecordController::class, 'unarchive']);
    
    // Lab Requests
    Route::apiResource('lab-requests', LabRequestController::class);
    Route::patch('/lab-requests/{labRequest}/update-status', [LabRequestController::class, 'updateStatus']);
    
    // Lab Results
    Route::apiResource('lab-results', LabResultController::class);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::patch('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    
    // Dashboard data
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard']);
    Route::get('/dashboard/doctor', [DashboardController::class, 'doctorDashboard']);
    Route::get('/dashboard/patient', [DashboardController::class, 'patientDashboard']);
    
    // Admin routes (should be protected by admin middleware in a real application)
    Route::apiResource('districts', DistrictController::class)->except(['index', 'show']);
    Route::apiResource('diseases', DiseaseController::class)->except(['index', 'show']);
    Route::apiResource('disease-outbreaks', DiseaseOutbreakController::class)->except(['index', 'show']);
    Route::apiResource('hospitals', HospitalController::class)->except(['index', 'show']);
    Route::apiResource('laboratories', LaboratoryController::class)->except(['index', 'show']);
    Route::apiResource('healthcare-providers', HealthcareProviderController::class)->except(['index', 'show']);
    Route::post('/notifications', [NotificationController::class, 'store']);
});

// Fallback route for undefined routes
Route::fallback(function () {
    return response()->json(['message' => 'API endpoint not found'], 404);
});