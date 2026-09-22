<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'provider_id',
    ];

    // العلاقة مع العميل
    public function client()
    {
        return $this->belongsTo(Client::class , 'client_id');
    }

    // العلاقة مع المزود
    public function provider()
    {
        return $this->belongsTo(Provider::class , 'provider_id');
    }
}
