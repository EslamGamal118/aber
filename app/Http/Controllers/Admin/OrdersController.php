<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
class OrdersController extends Controller
{
    // Display a listing of the resource.
public function index()
{
    $orders = Order::query()
        ->when(request('status') && request('status') !== 'all', fn($q) => $q->where('status', request('status')))
        ->when(request('payment_status') && request('payment_status') !== 'all', function($q) {
            $q->where('is_paid', request('payment_status') === 'paid');
        })
        ->when(request('search'), function($q) {
            $q->where(function($query) {
                $query->Where('buyerName', 'like', '%'.request('search').'%')
                        ->orWhere('id', 'like', '%'.request('search').'%')
                        ->orWhereHas('client', function($clientQuery) {
                        $clientQuery->where('name', 'like', '%'.request('search').'%')
                                    ->orWhere('phone', 'like', '%'.request('search').'%');
                    });
            });
        })
        ->with('client')
        ->orderBy('created_at', 'desc')
        ->paginate(10);
    
    return view('admin.orders.index', compact('orders'));
}

// Show Order details
public function show(Order $order)
{
    $items = OrderItem::where('order_id', $order->id)->get();
    return view('admin.orders.show', compact('order' , 'items'));
}

}