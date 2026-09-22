<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'title',
        'option',
        'price',
        'required'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'required' => 'boolean',
    ];

    /**
     * العلاقة مع المنتج
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * الحصول على مصفوفة الخيارات
     */
    public function getOptionsArrayAttribute()
    {
        return explode(',', $this->options);
    }

    /**
     * الحصول على مصفوفة الأسعار
     */
    public function getPricesArrayAttribute()
    {
        if (empty($this->prices)) {
            return array_fill(0, count($this->options_array), 0);
        }
        
        return array_map('floatval', explode(',', $this->prices));
    }

    /**
     * الحصول على الخيارات والأسعار كمصفوفة مرتبطة
     */
    public function getOptionsWithPricesAttribute()
    {
        $options = $this->options_array;
        $prices = $this->prices_array;
        
        $result = [];
        for ($i = 0; $i < count($options); $i++) {
            $result[] = [
                'name' => trim($options[$i]),
                'price' => isset($prices[$i]) ? $prices[$i] : 0
            ];
        }
        
        return $result;
    }
} 