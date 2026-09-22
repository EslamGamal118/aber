<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    // Get Vendor Analytics
    public function summary(){
        $provider = auth()->user();
        $today = Carbon::today();

        // Get total revenue
        $totalRevenueToday = Order::where('is_paid', true)
            ->where('provider_id', $provider->id)
            ->whereDate('created_at', $today)
            ->sum('total');

        // Get total orders
        $totalRevenue = Order::where('is_paid', true)->where('provider_id', $provider->id)->sum('total');

        // Get total orders
        $totalOrders = Order::where('is_paid', true )->where('provider_id', $provider->id)->count();

        return response()->json([
            'status' => 'success',
            'total_revenue_today' => $totalRevenueToday,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
        ]);
    }

    /*****************************************************************************/

    public function chart(Request $request)
{
    $type = $request->query('type', 'day'); // 'day', 'week', 'month', 'year'

    $vendorId = auth()->id();
    $query = Order::where('provider_id', $vendorId)
        ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
        ->where('is_paid', true)
        ->groupBy('date')
        ->orderBy('date');

    if ($type === 'day') {
        $query->whereDate('created_at', Carbon::today());
    } elseif ($type === 'week') {
        $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    } elseif ($type === 'month') {
        $query->whereMonth('created_at', Carbon::now()->month);
    } elseif ($type === 'year') {
        $query->whereYear('created_at', Carbon::now()->year);
    }

    $data = $query->get();

    return response()->json([
        'status' => true,
        'chart_type' => $type,
        'data' => $data,
    ]);
}

/**************************************************************************************/

public function getMostOrderedProducts()
{
    try {
        $providerId = auth()->user()->id;

        $topProducts = Product::select(
                'products.id',
                'products.name',
                'products.image_url',
                'products.price',
                DB::raw('SUM(order_items.quantity) as total_quantity')
            )
            ->join('order_items', 'products.id', '=', 'order_items.elementId')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.provider_id', $providerId)
            ->where('orders.is_paid', true) // unpaid orders never count for the provider
            ->groupBy('products.id', 'products.name', 'products.image_url', 'products.price')
            ->orderByDesc('total_quantity')
            ->take(10)
            ->get();

        return response()->json([
            'status' => true,
            'top_products' => $topProducts
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to fetch top products.',
            'error' => $e->getMessage()
        ], 500);
    }
}


}
