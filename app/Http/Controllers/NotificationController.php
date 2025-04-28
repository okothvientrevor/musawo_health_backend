<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        $notifications = Notification::where('user_id', $user->id)
            ->when($request->type, function ($query) use ($request) {
                return $query->where('type', $request->type);
            })
            ->when($request->has('read') && $request->read === '1', function ($query) {
                return $query->whereNotNull('read_at');
            })
            ->when($request->has('read') && $request->read === '0', function ($query) {
                return $query->whereNull('read_at');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);
            
        return response()->json($notifications);
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:appointment,lab_result,system,outbreak',
            'data' => 'nullable|json',
        ]);
        
        $notification = Notification::create($validated);
        
        return response()->json($notification, 201);
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification)
    {
        $user = Auth::user();
        
        if ($notification->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($notification);
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(Notification $notification)
    {
        $user = Auth::user();
        
        if ($notification->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $notification->delete();
        
        return response()->json(null, 204);
    }
    
    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        $user = Auth::user();
        
        if ($notification->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($notification->read_at) {
            return response()->json(['message' => 'Notification already read']);
        }
        
        $notification->update([
            'read_at' => now()
        ]);
        
        return response()->json($notification);
    }
    
    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        
        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->when($request->type, function ($query) use ($request) {
                return $query->where('type', $request->type);
            })
            ->update(['read_at' => now()]);
        
        return response()->json(['message' => 'All notifications marked as read']);
    }
    
    /**
     * Get unread notification count.
     */
    public function getUnreadCount()
    {
        $user = Auth::user();
        
        $count = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
        
        return response()->json(['unread_count' => $count]);
    }
}