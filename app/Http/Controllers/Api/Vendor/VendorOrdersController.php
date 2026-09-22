<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use Carbon\Carbon;
class VendorOrdersController extends Controller
{
public function getVendorOrders(Request $request)
{
    $providerId = auth()->id();

    // Payment-first: providers only ever see PAID orders (pending_payment / unpaid
    // orders are excluded by the visibleToProvider scope).
    $query = Order::with('client')
        ->where('provider_id', $providerId)
        ->visibleToProvider()
        ->orderBy('created_at', 'desc');

    if ($request->filled('filter')) {
        $query->filterByStatusOrDate($request->filter);
    }

    if ($request->filled('search')) {
        $query->search($request->search);
    }

    $orders = $query->get();

    foreach ($orders as $order) {
        // Load order items with product & options info
        $orderItems = $order->orderItems;  // assuming relation 'orderItems' exists

        foreach ($orderItems as $item) {
            // Attach product
            $item->product = Product::find($item->elementId);

            // Decode additions and group options
            if ($item->additions) {
                $itemAdditions = json_decode($item->additions, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($itemAdditions)) {
                    $productOptions = ProductOption::whereIn('id', $itemAdditions)->get();
                    $item->options = $this->groupProductOptions($productOptions);
                } else {
                    $item->options = [];
                }
                $item->additions = $itemAdditions;
            } else {
                $item->options = [];
            }
        }

        // Attach items back to order
        $order->items = $orderItems;

        // Optionally decode order-level additions (if needed)
        if ($order->additions) {
            $additions = json_decode($order->additions, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $order->additions = $additions;
                $order->productOptions = ProductOption::whereIn('id', $additions)->get();
            }
        }

        // Attach product for the order itself if elementId present
        if (!empty($order->elementId)) {
            $order->product = Product::find($order->elementId);
        }
    }

    return response()->json([
        'message' => 'تم الحصول على الطلبات بنجاح',
        'orders' => $orders
    ]);
}


/**************************************************************************************/
public function showVendorOrder(string $orderId, Request $request)
{
    $providerId = auth()->id();

    $order = Order::with(['client']) // eager load client only, product handled below
        ->where('id', $orderId)
        ->where('provider_id', $providerId)
        ->visibleToProvider() // unpaid orders do not exist from the provider's point of view
        ->first();

    if (!$order) {
        return response()->json([
            'message' => 'الطلب غير موجود أو ليس من صلاحياتك الوصول إليه'
        ], 404);
    }

    // Load order items and attach product + grouped options
    $orderItems = $order->orderItems; // assuming relationship exists

    foreach ($orderItems as $item) {
        // Attach product
        $item->product = Product::find($item->elementId);

        // Decode additions and group options
        if ($item->additions) {
            $itemAdditions = json_decode($item->additions, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($itemAdditions)) {
                $productOptions = ProductOption::whereIn('id', $itemAdditions)->get();
                $item->options = $this->groupProductOptions($productOptions);
            } else {
                $item->options = [];
            }
            $item->additions = $itemAdditions;
        } else {
            $item->options = [];
        }
    }
    $order->items = $orderItems;

    // Decode order-level additions if exists
    if ($order->additions) {
        $additions = json_decode($order->additions, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $order->additions = $additions;
            $order->productOptions = ProductOption::whereIn('id', $additions)->get();
        } else {
            return response()->json([
                'message' => 'فشل في فك تشفير الخيارات الإضافية'
            ], 500);
        }
    }

    // Attach product if elementId exists and not eager loaded
    if (!empty($order->elementId) && !$order->relationLoaded('product')) {
        $order->product = Product::find($order->elementId);
    }

    return response()->json([
        'message' => 'تم الحصول على تفاصيل الطلب بنجاح',
        'order' => $order
    ]);
}

/***************************************************************************************/
    public function updateVendorOrderStatus(Request $request, string $orderId)
{
    $request->validate([
        'status' => 'required|in:' . implode(',', Order::PROVIDER_STATUSES),
    ]);

    $providerId = auth()->id();

    // A provider can only act on paid orders; pending_payment orders are not
    // reachable here, so an unpaid order can never be accepted / dispatched.
    $order = Order::where('id', $orderId)
        ->where('provider_id', $providerId)
        ->visibleToProvider()
        ->first();

    if (!$order) {
        return response()->json([
            'message' => 'الطلب غير موجود أو ليس من صلاحياتك تعديله'
        ], 404);
    }

    $order->status = $request->status;
    $order->rejection_reason = $request->rejection_reason ?? null;
    $order->save();

    return response()->json([
        'message' => 'تم تحديث حالة الطلب بنجاح',
        'order' => $order
    ]);
}

/********************************************************************************/
    protected function groupProductOptions($productOptions)
{
    $grouped = [];

    foreach ($productOptions as $option) {
        $title = $option->title ?? 'بدون عنوان'; // fallback title if missing

        if (!isset($grouped[$title])) {
            $grouped[$title] = [
                'title' => $title,
                'required' => $option->required ?? false,
                'values' => []
            ];
        }

        $grouped[$title]['values'][] = [
            'option' => $option->option ?? '',
            'price' => $option->price ?? '0',
        ];
    }

    // Reindex array to have numeric keys
    return array_values($grouped);
}
}
