<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $users = User::when($request->role, function ($query) use ($request) {
            return $query->where('role', $request->role);
        })
        ->when($request->search, function ($query) use ($request) {
            return $query->where('first_name', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('national_id', 'like', "%{$request->search}%");
        })
        ->paginate($request->per_page ?? 15);
        
        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
            'national_id' => 'nullable|string|unique:users,national_id',
            'password' => 'required|string|min:8',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'blood_type' => 'nullable|string',
            'role' => 'required|in:patient,doctor,nurse,admin,lab_technician',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        // Handle profile image upload if provided
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $validated['profile_image'] = $path;
        }

        $validated['password'] = Hash::make($validated['password']);
        
        $user = User::create($validated);
        
        return response()->json($user, 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone_number' => ['sometimes', 'string', Rule::unique('users')->ignore($user->id)],
            'national_id' => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female,other',
            'blood_type' => 'nullable|string',
            'role' => 'sometimes|in:patient,doctor,nurse,admin,lab_technician',
            'profile_image' => 'nullable|image|max:2048',
            'status' => 'sometimes|in:active,inactive',
        ]);

        // Handle profile image upload if provided
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $validated['profile_image'] = $path;
        }

        // Update password if provided
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);
        
        return response()->json($user);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $user->delete();
        
        return response()->json(null, 204);
    }
}