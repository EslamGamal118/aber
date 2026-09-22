<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCard extends Model
{
    protected $table = 'payment_cards';

    protected $fillable = [
        'client_id',
        'card_number',
        'card_holder',
        'expiration',
        'cvv',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
