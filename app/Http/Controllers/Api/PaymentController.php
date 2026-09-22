<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::all();
        
        return response()->json([
            'message' => 'Payments retrieved successfully',
            'payments' => $payments
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string|max:10',
            'payment_method' => 'nullable|string|max:255',
            'clientId' => 'required|integer|exists:clients,id',
        ], ['clientId.exists' => 'Client ID does not exist in clients table']);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment = Payment::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'currency' => $request->currency ?? 'SAR',
            'payment_method' => $request->payment_method,
            'clientId' => $request->clientId
        ]);

        return response()->json([
            'message' => 'Payment created successfully',
            'payment' => $payment
        ], 201);
    }

    /**
     * Get payments by user ID.
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
        
        // Get all orders for the user
        $orders = Order::where('userId', $userId)->pluck('paymentId')->toArray();
        
        // Get all payments related to these orders
        $payments = Payment::whereIn('id', $orders)->get();
        
        return response()->json([
            'message' => 'Payments retrieved successfully',
            'payments' => $payments
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payment = Payment::with(['orders'])->find($id);
        
        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found'
            ], 404);
        }
        
        return response()->json([
            'message' => 'Payment retrieved successfully',
            'payment' => $payment
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::find($id);
        
        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
            'orderCount' => 'nullable|integer',
            'orders' => 'nullable|json',
            'orderId' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->update($request->all());
        
        return response()->json([
            'message' => 'Payment updated successfully',
            'payment' => $payment
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $payment = Payment::find($id);
        
        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found'
            ], 404);
        }
        
        $payment->delete();
        
        return response()->json([
            'message' => 'Payment deleted successfully'
        ]);
    }
}
