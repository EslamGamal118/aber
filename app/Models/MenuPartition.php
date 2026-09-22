<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuPartition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'menu_id',
        'description',
        'status'
    ];

    /**
     * Get the menu that owns the partition.
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /**
     * Get the elements associated with the partition.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'partition_id');
    }
}
