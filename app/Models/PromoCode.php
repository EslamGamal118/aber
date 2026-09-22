<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = [
        'provider_id', 'code', 'discount', 'expiry_date'
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
