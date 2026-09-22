<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
class Provider extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'car_name',
        'provider_banner',
        'email',
        'phone',
        'password',
        'id_card',
        'status',
        'approved_at',
        'phone_verified_at',
        'is_open',
        'latitude',
        'longitude',
        'bio',
        'address',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Check if the provider is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if the provider's phone is verified.
     *
     * @return bool
     */
    public function isPhoneVerified()
    {
        return $this->phone_verified_at !== null;
    }

    /**
     * Check if the provider is pending approval.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the provider is rejected.
     *
     * @return bool
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if the provider is blocked.
     *
     * @return bool
     */
    public function isBlocked()
    {
        return $this->status === 'blocked';
    }

    public function products(){
        return $this->hasMany(Product::class, 'vendor_id');
    }
    
    public function ratings()
{
    return $this->hasMany(Rating::class, 'provider_id');
}

public function menus()
{
    return $this->hasMany(Menu::class);
}

}
