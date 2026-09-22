<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
     protected $table = 'timetables';

    protected $fillable = ['provider_id', 'seller_id', 'date', 'start_time', 'end_time', 'status'];

    public function provider()
    {
        return $this->belongsTo(Provider::class , 'provider_id');
    }
}
