<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'national_id' => 'nullable|string|unique:users,national_id',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'blood_type' => 'nullable|string',
            'profile_image' => 'nullable|image|max:2048',
        ]);
        
        // By default, register as a patient
        $validated['role'] = 'patient';
        $validated['status'] = 'active';
        $validated['password'] = Hash::make($validated['password']);
        
        // Handle profile image upload if provided
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $validated['profile_image'] = $path;
        }
        
        $user = User::create($validated);
        
        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }
    
    /**
     * Login user and create token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required_without:national_id|string|email',
            'national_id' => 'required_without:email|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);
        
        // Find user by email or national ID
        $user = null;
        if ($request->has('email')) {
            $user = User::where('email', $request->email)->first();
        } else {
            $user = User::where('national_id', $request->national_id)->first();
        }
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        // Check if user is active
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact support.'],
            ]);
        }
        
        // Create token
        $deviceName = $request->device_name ?? 'Unknown Device';
        $token = $user->createToken($deviceName)->plainTextToken;
        
        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
    
    /**
     * Login with national ID and PIN.
     */
    public function nationalIdLogin(Request $request)
    {
        $request->validate([
            'national_id' => 'required|string',
            'pin' => 'required|string',
        ]);
        
        $user = User::where('national_id', $request->national_id)->first();
        
        if (!$user || !Hash::check($request->pin, $user->password)) {
            throw ValidationException::withMessages([
                'national_id' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        // Check if user is active
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'national_id' => ['Your account is not active. Please contact support.'],
            ]);
        }
        
        // Create token
        $token = $user->createToken('NIN_Login')->plainTextToken;
        
        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
    
    /**
     * Get the authenticated user.
     */
    public function user(Request $request)
    {
        return response()->json($request->user()->load('patientRecord'));
    }
    
    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Logged out successfully']);
    }
    
    /**
     * Change password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);
        
        $user = $request->user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match our records.'],
            ]);
        }
        
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);
        
        return response()->json(['message' => 'Password changed successfully']);
    }
    
    /**
     * Request password reset.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['message' => 'If your email is registered, you will receive a password reset link.']);
        }
        
        // Generate password reset token (would send email in a real application)
        // For this demo, we'll just return a success message
        
        return response()->json(['message' => 'If your email is registered, you will receive a password reset link.']);
    }
}
