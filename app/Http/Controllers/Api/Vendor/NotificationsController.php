<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
class NotificationsController extends Controller
{
    // get all notifications for current seller
    public function index(Request $request){
        try{
            if ($request->has('category')) {
                $notifications = Notification::where('provider_id',Auth::user()->id)
                ->where('sender_type', 'client')
                ->where('category', $request->category)->get();
                return response()->json(['status' => 'success', 'data' => $notifications]);
            }
            $notifications = Notification::where('provider_id',Auth::user()->id)
            ->where('sender_type', 'client')->get();
            return response()->json(['status' => 'success', 'data' => $notifications]);
        }catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    /******************************************************************************************/
    public function store(Request $request)
{
    // Validate the incoming request data
    $validatedData = $request->validate([
        'client_id'   => 'required|exists:clients,id',
        'title'       => 'required|string|max:255',
        'content'     => 'required|string',
        'category'    => 'nullable|string|max:100',
    ]);

    // Create a new notification
    $notification = Notification::create([
        'client_id'   => $validatedData['client_id'] ?? null,
        'provider_id' => Auth::user()->id,
        'title'       => $validatedData['title'],
        'content'     => $validatedData['content'],
        'sender_type' => 'provider',
        'category'    => $validatedData['category'] ?? null,
    ]);

    return response()->json([
        'message' => 'Notification created successfully.',
        'notification' => $notification
    ], 201);
}

    /******************************************************************************************/
    // Mark Notification As Read
    public function markAsRead($id){
        try{
            Notification::where('id', $id)->update(['is_read' => true]);
            return response()->json(['status' => 'success', 'message' => 'تم تحديث الاشعار بنجاح']);
        }catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    /******************************************************************************************/

    // Mark All Notifications As Read
    public function markAllAsRead(){
        try{
            Notification::where('provider_id',Auth::user()->id)
            ->where('sender_type', 'client')
            ->update(['is_read' => true]);
            return response()->json(['status' => 'success', 'message' => 'تم تحديث الاشعارات بنجاح']);
        }catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }
}
