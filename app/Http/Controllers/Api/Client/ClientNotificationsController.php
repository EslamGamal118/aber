<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class ClientNotificationsController extends Controller
{
    // Get all notifications for the current client
    public function index(Request $request)
    {
        try {
            $clientId = Auth::id(); // Assuming client is authenticated using Sanctum or API guard

            $query = Notification::where('client_id', $clientId)
            ->where('sender_type', 'provider');

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            $notifications = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $notifications
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ]);
        }
    }

    /******************************************************************************************/
      public function store(Request $request)
{
    // Validate the incoming request data
    $validatedData = $request->validate([
        'provider_id'   => 'required|exists:providers,id',
        'title'       => 'required|string|max:255',
        'content'     => 'required|string',
        // "new_order" / order alerts are owned by the payment pipeline
        // (PaymentService -> OrderPaid) and can never be raised by a client directly.
        'category'    => 'nullable|string|max:100|not_in:' . implode(',', \App\Services\ProviderNotificationService::RESERVED_CATEGORIES),
    ]);

    // Create a new notification
    $notification = Notification::create([
        'client_id' => Auth::user()->id,
        'provider_id'   => $validatedData['provider_id'] ?? null,
        'title'       => $validatedData['title'],
        'content'     => $validatedData['content'],
        'sender_type' => 'client',
        'category'    => $validatedData['category'] ?? null,
    ]);

    return response()->json([
        'message' => 'Notification created successfully.',
        'notification' => $notification
    ], 201);
}

    /******************************************************************************************/

    // Mark a specific notification as read
    public function markAsRead($id)
    {
        try {
            Notification::where('id', $id)->update(['is_read' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث الإشعار بنجاح'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ]);
        }
    }

    /******************************************************************************************/

    // Mark all notifications as read for the current client
    public function markAllAsRead()
    {
        try {
            Notification::where('client_id', Auth::id())
                        ->where('sender_type', 'provider')
                        ->update(['is_read' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث الإشعارات بنجاح'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ]);
        }
    }
}
