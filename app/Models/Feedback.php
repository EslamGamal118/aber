<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'userId',
        'orderId',
        'car_id',
        'rating',
        'comment',
    ];

    /**
     * علاقة مع العميل
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * علاقة مع العربة
     */
    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    /**
     * علاقة مع الطلب
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
