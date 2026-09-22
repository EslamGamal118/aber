<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $table = 'offers';

    protected $fillable = [
        'title',
        'image',
        'code',
        'discount_type',
        'discount_value',
        'provider_id',
        'created_by_id',
        'created_by_type',
        'max_uses',
        'uses',
        'start_date',
        'end_date',
        'status',
    ];

     protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

}
