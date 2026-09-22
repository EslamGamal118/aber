<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'elementId',
        'total',
        'price',
        'quantity',
        'additions',
        'additionPrice'

    ];

    /**
     * Get the order that owns the order item.
     */
public function order()
{
    return $this->belongsTo(Order::class);
}

public function product()
{
    return $this->belongsTo(Product::class);
}

    /**
     * Get the menu element associated with the order item.
     */
    public function element()
    {
        return $this->belongsTo(Element::class, 'elementId');
    }

} 