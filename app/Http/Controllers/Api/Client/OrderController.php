<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Interfaces\PaymentGatewayInterface;
use App\Services\PaymentService;
use App\Models\Order;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Payment;
use App\Models\PromoCode;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductOption;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
class OrderController extends Controller
{

    /**
     * Al Rajhi Bank is bound to PaymentGatewayInterface (see AppServiceProvider)
     * and is the only gateway used for checkout.
     */
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway,
        protected PaymentService $paymentService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
   public function index()
{
    
    $orders = Order::with(['items'])->where('userId', auth()->id())->orderBy('created_at', 'desc')->get();

    foreach ($orders as $order) {
        foreach ($order->items as $item) {
            // Attach the product
            $product = Product::find($item->elementId);
            $item->product = $product;

            // Handle additions (product options)
            if (!empty($item->additions)) {
                $additionsIds = json_decode($item->additions, true);
                if (is_array($additionsIds)) {
                    $productOptions = ProductOption::whereIn('id', $additionsIds)->get();
                    $item->productOptions = $productOptions;
                }
            }
        }
    }

    return response()->json([
        'message' => 'Orders retrieved successfully',
        'orders' => $orders
    ]);
}
    
    /******************************************************************************************/
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'items' => 'required|array|min:1',
        'items.*.elementId' => 'required|integer',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.additions' => 'nullable|array',
        'items.*.additions.*' => 'integer',
        'items.*.additionPrice' => 'nullable|numeric',
        'promoCodeDiscount' => 'nullable|integer',
        'provider_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    $client = Client::find(auth()->id());
    if (!$client) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $itemsData = $request->input('items');
    $promoCodeDiscount = $request->input('promoCodeDiscount', 0);
    $totalAmount = 0;

    DB::beginTransaction();

    try {
        // Payment-first workflow: the order starts as pending_payment / unpaid.
        // It is NOT visible to the provider and NO provider notification is
        // triggered here. Release + notification happen exclusively in
        // PaymentService::completeOrderPayment() after Al Rajhi returns CAPTURED.
        $order = Order::create([
            'userId' => $client->id,
            'provider_id' => $request->provider_id,
            'promoCodeDiscount' => $promoCodeDiscount,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => Order::PAYMENT_METHOD_ALRAJHI,
            'payment_status' => Order::PAYMENT_STATUS_PENDING,
            'is_paid' => false,
            'total' => 0,
        ]);

        // Calculate total and create order items
        foreach ($itemsData as $item) {
            $product = Product::find($item['elementId']);
            if (!$product) {
                DB::rollBack();
                return response()->json(['message' => "Product with ID {$item['elementId']} not found"], 404);
            }

            $quantity = $item['quantity'];
            $additionPrice = $item['additionPrice'] ?? 0;
            $itemTotal = ($product->price * $quantity) + $additionPrice;
            $totalAmount += $itemTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'elementId' => $product->id,
                'price' => $product->price,
                'quantity' => $quantity,
                'additions' => isset($item['additions']) ? json_encode($item['additions']) : null,
                'additionPrice' => $additionPrice,
                'total' => $itemTotal,
            ]);
        }

        // Deduct promo code discount and ensure total is not negative
        $finalTotal = max($totalAmount - $promoCodeDiscount, 0);
        $order->update(['total' => $finalTotal]);

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Order creation failed', ['error' => $e->getMessage()]);

        return response()->json([
            'message' => 'Order failed',
            'error' => env('APP_DEBUG') ? $e->getMessage() : null
        ], 500);
    }

    // Initiate the Al Rajhi hosted payment page outside the DB transaction
    // (external HTTP call). The order already exists, so a gateway failure
    // can be retried through POST /client/orders/{orderId}/pay.
    $payment = $this->initiatePayment($order);

    // Response: the order record + the Al Rajhi Hosted Payment Page URL only.
    return response()->json([
        'message' => $payment['success']
            ? 'Order created successfully, awaiting payment'
            : 'Order created but payment could not be initiated, please retry',
        'order' => $order->fresh('items'),
        'payment_gateway' => Order::PAYMENT_METHOD_ALRAJHI,
        'url' => $payment['success'] ? $payment['url'] : null,
        'payment_url' => $payment['success'] ? $payment['url'] : null,
        'payment_link' => $payment['success'] ? $payment['url'] : null, // backward compatible key
        'payment_error' => $payment['success'] ? null : $payment['message'],
    ], $payment['success'] ? 201 : 502);
}

    /********************************************************************************/
    /**
     * (Re)request an Al Rajhi hosted payment page URL for an unpaid order.
     */
    public function pay(string $orderId)
    {
        $order = Order::where('userId', auth()->id())->find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->isPaid()) {
            return response()->json([
                'message' => 'Order is already paid',
                'payment_status' => $order->payment_status,
            ], 409);
        }

        if (!$order->isPayable()) {
            return response()->json([
                'message' => 'Order cannot be paid',
                'status' => $order->status,
                'payment_status' => $order->payment_status,
            ], 422);
        }

        $payment = $this->initiatePayment($order);

        if (!$payment['success']) {
            return response()->json([
                'message' => 'Payment could not be initiated, please retry',
                'payment_error' => $payment['message'],
            ], 502);
        }

        return response()->json([
            'message' => 'Payment initiated successfully',
            'order_id' => $order->id,
            'payment_gateway' => Order::PAYMENT_METHOD_ALRAJHI,
            'url' => $payment['url'],
            'payment_url' => $payment['url'],
            'payment_link' => $payment['url'],
        ]);
    }

    /********************************************************************************/
    /**
     * Lightweight payment status check for the mobile app after the hosted
     * payment page closes.
     */
    public function paymentStatus(string $orderId)
    {
        $order = Order::where('userId', auth()->id())->find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'order_id' => $order->id,
            'reference' => $order->reference,
            'status' => $order->status,
            'is_paid' => $order->isPaid(),
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'transaction_id' => $order->transaction_id,
            'paid_at' => $order->paid_at,
        ]);
    }

    /**
     * Ask Al Rajhi for a hosted payment page URL and persist it on the order.
     *
     * @return array{success: bool, url: string, payment_id?: string|null, message?: string|null}
     */
    protected function initiatePayment(Order $order): array
    {
        try {
            $payment = $this->paymentGateway->sendPayment($order);
        } catch (\Throwable $e) {
            Log::error('Al Rajhi payment initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'url' => '', 'message' => 'Payment gateway unavailable'];
        }

        if ($payment['success']) {
            $this->paymentService->recordPaymentInitiation($order, $payment);
        }

        return $payment;
    }


/****************************************************************************************/

public function show(string $id)
{
    
    // Retrieve the order with related models
    $order = Order::with(['client', 'promoCode'])->find($id);

    if (!$order) {
        return response()->json([
            'message' => 'Order not found'
        ], 404);
    }

    // Get all order items for this order
    $items = OrderItem::where('order_id', $order->id)->get();

    foreach ($items as $item) {
        // Attach product for each item
        if (!empty($item->elementId)) {
            $product = Product::find($item->elementId);
            $item->product = $product;
        }

        // Decode additions and group product options
        if (!empty($item->additions)) {
            $additions = json_decode($item->additions, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'message' => 'Error decoding additions data',
                ], 500);
            }

            $productOptions = ProductOption::whereIn('id', $additions)->get();
            $item->options = $this->groupProductOptions($productOptions);
        }
    }

    // Attach items collection to the order
    $order->items = $items;

    return response()->json([
        'message' => 'Order retrieved successfully',
        'order' => $order
    ]);
}

    /********************************************************************************/
 public function getByUser()
{
    $userId = auth()->user()->id;

    $user = Client::find($userId);
    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    $orders = Order::where('userId', $userId)->orderBy('created_at', 'desc')->get();

    foreach ($orders as $order) {
        // Load items for the order
        $items = OrderItem::where('order_id', $order->id)->get();

        foreach ($items as $item) {
            // Attach product
            if (!empty($item->elementId)) {
                $product = Product::find($item->elementId);
                $item->product = $product;
            }

            // Decode additions and group options
            if (!empty($item->additions)) {
                $additions = json_decode($item->additions, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'message' => 'Error decoding additions data',
                    ], 500);
                }

                $productOptions = ProductOption::whereIn('id', $additions)->get();
                $item->options = $this->groupProductOptions($productOptions);
            }
        }

        $order->items = $items;
    }

    return response()->json([
        'message' => 'Orders retrieved successfully',
        'orders' => $orders,
    ]);
}

    /********************************************************************************/
    
    public function updateStatus(Request $request, string $orderId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::find($orderId);
        
        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }
        
        // Clients may only cancel; every other status is set by the payment pipeline or the provider.
        if ($request->status !== Order::STATUS_CANCELLED) {
            return response()->json([
                'message' => 'Order status can only be changed by the provider once the order is paid',
            ], 422);
        }

        $order->status = Order::STATUS_CANCELLED;
        $order->save();
        
        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order
        ]);
    }
    
    /********************************************************************************/
public function update(Request $request, string $id)
{
    $order = Order::find($id);

    if (!$order) {
        return response()->json([
            'message' => 'Order not found'
        ], 404);
    }

    $validator = Validator::make($request->all(), [
        'status' => 'nullable|string',
        'buyerName' => 'nullable|string',
        'promoCodeId' => 'nullable|integer',
        'items' => 'nullable|array',
        'items.*.id' => 'nullable|integer', // For updating existing items
        'items.*.elementId' => 'required|integer',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.additions' => 'nullable|array',
        'items.*.additionPrice' => 'nullable|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    // Payment-first guards:
    //  - the operational status is owned by the payment pipeline / provider; a
    //    client may only cancel (never move an unpaid order into the provider feed)
    //  - a paid order's items / total are frozen (the captured amount must match)
    if ($request->filled('status') && $request->status !== Order::STATUS_CANCELLED) {
        return response()->json([
            'message' => 'Order status can only be changed by the provider once the order is paid',
        ], 422);
    }

    if ($order->isPaid() && ($request->has('items') || $request->has('promoCodeDiscount'))) {
        return response()->json([
            'message' => 'A paid order cannot be modified',
        ], 422);
    }

    $order->update($request->only(['status', 'promoCodeDiscount']));

    $total = 0;

    if ($request->has('items')) {
        // Delete existing items if you're replacing them completely (optional)
        $order->items()->delete();

        foreach ($request->items as $itemData) {
            $product = Product::find($itemData['elementId']);

            if (!$product) {
                return response()->json([
                    'message' => "Product with ID {$itemData['elementId']} not found"
                ], 404);
            }

            $quantity = $itemData['quantity'];
            $additionPrice = $itemData['additionPrice'] ?? 0;
            $additions = isset($itemData['additions']) ? json_encode($itemData['additions']) : null;

            $itemTotal = ($product->price + $additionPrice) * $quantity;
            $total += $itemTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'elementId' => $product->id,
                'quantity' => $quantity,
                'additions' => $additions,
                'additionPrice' => $additionPrice,
                'total' => $itemTotal,
                'price' => $product->price
            ]);
        }

        // Update the order's total price
        $order->update(['total' => $total  - $order->promoCodeDiscount]);
    }

    return response()->json([
        'message' => 'Order updated successfully',
        'order' => $order->load('items')
    ]);
}



    /********************************************************************************/
    public function destroy(string $id)
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }
        
        $order->delete();
        
        return response()->json([
            'message' => 'Order deleted successfully'
        ]);
    }

    /********************************************************************************/
    public function cancelOrder(string $orderId)
    {
        $order = Order::where('userId', auth()->id())->find($orderId);
        
        if (!$order) {
            return response()->json([
                'message' => 'الطلب غير موجود'
            ], 404);
        }
        
        $order->status = Order::STATUS_CANCELLED;
        $order->save();
        
        return response()->json([
            'message' => 'تم إلغاء الطلب بنجاح',
            'order' => [
                'id' => $order->id,
                'status' => $order->status
            ]
        ]);
    }

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

