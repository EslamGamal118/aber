<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'vendor_id',
        'image_url',
        'price',
        'is_available',
        'preparation_time',
        'discount_price',
        'partition_id',
        'menu_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'discount_price' => 'float',
        'is_available' => 'boolean',
        'avg_rating' => 'float',
        'ratings_count' => 'integer',
        'preparation_time' => 'integer',
    ];

    /**
     * العلاقة مع البائع (المزود)
    */
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'vendor_id');
    }
    public function vendor()
    {
        return $this->belongsTo(Provider::class, 'vendor_id');
    }

    /**
     * العلاقة مع التصنيف
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * العلاقة مع خيارات المنتج
     */
    public function options()
    {
        return $this->hasMany(ProductOption::class);
    }

    /**
     * العلاقة مع تقييمات المنتج
     */
    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * حساب السعر الفعلي للمنتج (مع مراعاة الخصم إن وجد)
     */
    public function getActualPriceAttribute()
    {
        return $this->discount_price ?? $this->price;
    }

    /**
     * التحقق من وجود خصم على المنتج
     */
    public function getHasDiscountAttribute()
    {
        return !is_null($this->discount_price) && $this->discount_price < $this->price;
    }

    /**
     * حساب قيمة الخصم
     */
    public function getDiscountValueAttribute()
    {
        if ($this->has_discount) {
            return $this->price - $this->discount_price;
        }
        
        return 0;
    }

    /**
     * حساب نسبة الخصم
     */
    public function getDiscountPercentageAttribute()
    {
        if ($this->has_discount && $this->price > 0) {
            return round(($this->discount_value / $this->price) * 100);
        }
        
        return 0;
    }

    public function order()
    {
        return $this->belongsToMany(Order::class);
    }

    public function productOptions()
{
    return $this->hasMany(ProductOption::class);
}

public function orderItems()
{
    return $this->hasMany(OrderItem::class, 'elementId'); // Foreign key
}
} 