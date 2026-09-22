<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    /**
     * Audiences an OTP can be issued for (stored in user_type).
     */
    public const TYPE_CLIENT = 'client';
    public const TYPE_VENDOR = 'vendor';
    public const TYPES = [self::TYPE_CLIENT, self::TYPE_VENDOR];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'verified',
        'user_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'verified' => 'boolean',
    ];
} 