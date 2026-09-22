<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notifications = Notification::all();
        
        return response()->json([
            'message' => 'Notifications retrieved successfully',
            'notifications' => $notifications
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'type' => 'nullable|string',
            'expireAt' => 'nullable|date',
            'userId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user exists
        $user = User::find($request->userId);
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $notification = Notification::create([
            'message' => $request->message,
            'type' => $request->type,
            'isRead' => false,
            'expireAt' => $request->expireAt,
            'userId' => $request->userId
        ]);

        return response()->json([
            'message' => 'Notification created successfully',
            'notification' => $notification
        ], 201);
    }

    /**
     * Get notifications by user ID.
     */
    public function getByUser(string $userId)
    {
        // Check if user exists
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        
        $notifications = Notification::where('userId', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'message' => 'Notifications retrieved successfully',
            'notifications' => $notifications
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $notificationId)
    {
        $notification = Notification::find($notificationId);
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }
        
        $notification->isRead = true;
        $notification->save();
        
        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $notification = Notification::find($id);
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }
        
        return response()->json([
            'message' => 'Notification retrieved successfully',
            'notification' => $notification
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $notification = Notification::find($id);
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string',
            'type' => 'nullable|string',
            'isRead' => 'nullable|boolean',
            'expireAt' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $notification->update($request->all());
        
        return response()->json([
            'message' => 'Notification updated successfully',
            'notification' => $notification
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $notification = Notification::find($id);
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }
        
        $notification->delete();
        
        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }
}
