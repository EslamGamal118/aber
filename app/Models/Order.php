<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    /**
     * Al Rajhi Bank is the single, exclusive payment gateway.
     */
    public const PAYMENT_METHOD_ALRAJHI = 'alrajhi';

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';

    /**
     * Operational (provider facing) statuses.
     *
     * Payment-first workflow: an order is created as STATUS_PENDING_PAYMENT and
     * is invisible to the provider. Only PaymentService::completeOrderPayment()
     * (Al Rajhi "CAPTURED") moves it to STATUS_PENDING, which is the provider's
     * "new order awaiting acceptance" state.
     */
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FOR_DELIVERY = 'for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Statuses a provider may set on a (paid) order.
     */
    public const PROVIDER_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_DONE,
        self::STATUS_CANCELLED,
    ];

    /**
     * Default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING_PAYMENT,
        'payment_method' => self::PAYMENT_METHOD_ALRAJHI,
        'payment_status' => self::PAYMENT_STATUS_PENDING,
        'is_paid' => false,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'partitionId',
        'type',
        'quantity',
        'paymentId',
        'imageUrl',
        'buyerName',
        'status',
        'userId',
        'promoCodeId',
        'size',
        'additions',
        'additionPrice',
        'total',
        'addressId',
        'client_id',
        'provider_id',
        'reference',
        'promoCodeDiscount',
        'amount',
        'payment_method',
        'payment_status',
        'is_paid',
        'transaction_id',
        'payment_reference',
        'payment_link',
        'paid_at',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    /**
     * Boot function to add hooks
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate reference when creating a new order
        static::creating(function ($order) {
            if (!$order->reference) {
                $order->reference = '#' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Whether the order has been captured by Al Rajhi.
     */
    public function isPaid(): bool
    {
        return (bool) $this->is_paid || $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    /**
     * Whether a hosted payment page can still be requested for this order.
     */
    public function isPayable(): bool
    {
        return !$this->isPaid() && $this->status !== self::STATUS_CANCELLED && (float) $this->total > 0;
    }

    /**
     * Whether the order is still waiting for the customer to pay.
     */
    public function isAwaitingPayment(): bool
    {
        return !$this->isPaid();
    }

    /**
     * Whether the provider is allowed to see / act on this order.
     * Strictly: payment captured AND the order has left the pending_payment state.
     */
    public function isVisibleToProvider(): bool
    {
        return $this->isPaid() && $this->status !== self::STATUS_PENDING_PAYMENT;
    }

    /**
     * Orders the provider is allowed to see: paid only. Unpaid / pending_payment
     * orders never reach the provider feed, dashboard or notifications.
     */
    public function scopeVisibleToProvider($query)
    {
        return $query->where('is_paid', true)
            ->where('payment_status', self::PAYMENT_STATUS_PAID)
            ->where('status', '!=', self::STATUS_PENDING_PAYMENT);
    }

    /**
     * Orders still waiting for payment (never shown to providers).
     */
    public function scopeAwaitingPayment($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Get the client associated with the order.
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'userId');
    }

    /**
     * Get the provider associated with the order.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    /**
     * Get the payment associated with the order.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'paymentId');
    }

    /**
     * Get the promo code associated with the order.
     */
    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promoCodeId');
    }

    /**
     * Get the customer associated with the order.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }

    /**
     * Get the address associated with the order.
     */
    public function address()
    {
        return $this->belongsTo(NewAddress::class, 'addressId');
    }

    /**
     * Get the order items associated with the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Get the comments associated with the order.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'orderId');
    }

    /**
     * Get the feedbacks associated with the order.
     */
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'orderId');
    }

    // app/Models/Order.php

    public function product()
    {
        return $this->belongsTo(Product::class, 'elementId');
    }

// Filter orders by status or date
public function scopeFilterByStatusOrDate($query, $filter)
{
    switch ($filter) {
        case 'today':
            return $query->whereDate('created_at', Carbon::today());

        case 'week':
            return $query->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ]);

        case self::STATUS_CANCELLED:
        case self::STATUS_PENDING:
        case self::STATUS_ACCEPTED:
        case self::STATUS_REJECTED:
        case self::STATUS_FOR_DELIVERY:
        case self::STATUS_DELIVERED:
        case self::STATUS_DONE:
            return $query->where('status', $filter);

        default:
            return $query;
    }
}

// Search orders by client name or product name
public function scopeSearch($query, $term)
{
    return $query->where(function ($q) use ($term) {
        $q->whereHas('client', function ($clientQuery) use ($term) {
            $clientQuery->where('name', 'like', "%$term%");
        });

        $q->orWhereHas('product', function ($productQuery) use ($term) {
            $productQuery->where('name', 'like', "%$term%");
        });
    });
}

public function orderItems()
{
    return $this->hasMany(OrderItem::class);
}




}
