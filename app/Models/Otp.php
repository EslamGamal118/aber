<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $phone       always stored normalised (see PhoneNumber::normalize)
 * @property string $code
 * @property bool   $verified
 * @property int    $attempts
 * @property string $user_type
 */
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
        'verified_at',
        'attempts',
        'user_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'verified' => 'boolean',
        'attempts' => 'integer',
    ];

    /**
     * Guarantee the canonical format no matter which caller writes the record.
     * This is what stops "+966500000001" and "0500000001" becoming two rows.
     */
    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = PhoneNumber::normalize($value);
    }

    /**
     * Codes are compared as strings ("0123" !== "123"), so never let the DB
     * driver hand one back as an int.
     */
    public function getCodeAttribute($value): string
    {
        return (string) $value;
    }

    public function scopeForPhone(Builder $query, ?string $phone): Builder
    {
        return $query->where('phone', PhoneNumber::normalize($phone));
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $type === null ? $query : $query->where('user_type', $type);
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || Carbon::now()->greaterThan($this->expires_at);
    }

    /**
     * Verified, and verified recently enough to still be redeemable by
     * register / reset-password. A `verified` flag on its own never expires,
     * which would let an old OTP authorise an account months later.
     */
    public function isFreshlyVerified(): bool
    {
        if (! $this->verified || $this->verified_at === null) {
            return false;
        }

        return $this->verified_at->greaterThanOrEqualTo(
            Carbon::now()->subMinutes((int) config('otp.verification_ttl_minutes', 30))
        );
    }

    public function hasExhaustedAttempts(): bool
    {
        return $this->attempts >= (int) config('otp.max_attempts', 5);
    }
}
