<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'birthdate' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Check if the client's phone is verified
     */
    public function hasVerifiedPhone()
    {
        return ! is_null($this->phone_verified_at);
    }

    /**
     * Mark the client's phone as verified
     */
    public function markPhoneAsVerified()
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * العلاقة مع قائمة المفضلة
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * العلاقة مع عربات الطعام المفضلة
     */
    public function favoriteCars()
    {
        return $this->belongsToMany(Car::class, 'favorites', 'client_id', 'car_id');
    }

    /**
     * العلاقة مع عناوين العميل
     */
    public function addresses()
    {
        return $this->hasMany(NewAddress::class, 'userId');
    }

    /**
     * العلاقة مع العناوين التفصيلية للعميل
     */
    public function detailedAddresses()
    {
        return $this->hasMany(NewAddress2::class, 'userId');
    }
}
