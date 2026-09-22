<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
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
        'provider_id',
    ];

    /**
     * Get the partitions associated with the menu.
     */
    public function partitions()
    {
        return $this->hasMany(MenuPartition::class, 'menu_id');
    }


    /**
     * Get the car that owns the menu.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
