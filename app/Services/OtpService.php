<?php

namespace App\Services;

use App\Models\Otp;
use App\Support\PhoneNumber;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The one place that issues, verifies and redeems OTPs.
 *
 * Every controller in the pipeline (registration, vendor registration, client
 * reset-password, vendor reset-password) used to carry its own copy of the
 * phone matching, the bypass list and the "mark verified" logic, and the
 * copies had drifted apart. That drift is what produced the
 * "Phone number not verified" failures.
 */
class OtpService
{
    public const OK = 'ok';
    public const NOT_FOUND = 'not_found';
    public const EXPIRED = 'expired';
    public const INVALID = 'invalid';
    public const TOO_MANY_ATTEMPTS = 'too_many_attempts';
    public const COOLDOWN = 'cooldown';

    /**
     * Create (or refresh) the OTP for a phone + audience.
     *
     * @return array{status: string, otp: ?Otp, code: ?string, bypassed: bool, retry_after: int}
     */
    public function issue(string $phone, string $type): array
    {
        $normalized = PhoneNumber::normalize($phone);
        $bypassed = PhoneNumber::isBypassed($normalized);

        $existing = Otp::forPhone($normalized)->ofType($type)->first();

        // Throttle resends per number so the SMS gateway cannot be used as an
        // outbound spam / cost amplifier. Bypassed numbers cost nothing.
        $cooldown = (int) config('otp.resend_cooldown_seconds', 60);

        if (! $bypassed && $cooldown > 0 && $existing && ! $existing->verified && $existing->updated_at) {
            $elapsed = (int) $existing->updated_at->diffInSeconds(Carbon::now(), true);

            if ($elapsed < $cooldown) {
                return [
                    'status' => self::COOLDOWN,
                    'otp' => $existing,
                    'code' => null,
                    'bypassed' => false,
                    'retry_after' => $cooldown - $elapsed,
                ];
            }
        }

        $code = $bypassed
            ? (string) config('otp.bypass_code', '1234')
            : str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $otp = $this->upsert($normalized, $type, [
            'code' => $code,
            'verified' => false,
            'verified_at' => null,
            'attempts' => 0,
            'expires_at' => Carbon::now()->addMinutes((int) config('otp.ttl_minutes', 10)),
        ]);

        // Never write a live OTP to the log file in production.
        Log::info('OTP issued', [
            'phone' => PhoneNumber::mask($normalized),
            'user_type' => $type,
            'bypassed' => $bypassed,
            'code' => app()->environment('production') ? '****' : $code,
        ]);

        return [
            'status' => self::OK,
            'otp' => $otp,
            'code' => $code,
            'bypassed' => $bypassed,
            'retry_after' => 0,
        ];
    }

    /**
     * Check a submitted code and, on success, persist verified = true together
     * with the audience, for BOTH regular and bypassed numbers.
     *
     * $type may be null when the client app does not send one; the record
     * created by sendOtp is then used as-is. For a bypassed number with no
     * record at all, every audience is verified so no downstream register call
     * can fail on a missing row.
     *
     * @return array{status: string, otp: ?Otp, attempts_left: int}
     */
    public function verify(string $phone, string $code, ?string $type = null): array
    {
        $normalized = PhoneNumber::normalize($phone);
        $code = trim($code);
        $maxAttempts = (int) config('otp.max_attempts', 5);

        // Bypassed numbers answer to the fixed code regardless of what is (or
        // is not) stored, but they still get a real, verified row written.
        if (PhoneNumber::isBypassed($normalized)) {
            if (! hash_equals((string) config('otp.bypass_code', '1234'), $code)) {
                return ['status' => self::INVALID, 'otp' => null, 'attempts_left' => $maxAttempts];
            }

            $targets = $type !== null ? [$type] : $this->audiencesFor($normalized);
            $verified = null;

            foreach ($targets as $audience) {
                $verified = $this->upsert($normalized, $audience, [
                    'code' => (string) config('otp.bypass_code', '1234'),
                    'verified' => true,
                    'verified_at' => Carbon::now(),
                    'attempts' => 0,
                    'expires_at' => Carbon::now()->addMinutes((int) config('otp.ttl_minutes', 10)),
                ]);
            }

            return ['status' => self::OK, 'otp' => $verified, 'attempts_left' => $maxAttempts];
        }

        return DB::transaction(function () use ($normalized, $code, $type, $maxAttempts) {
            // Row lock: two concurrent guesses must not both read attempts = 4.
            $records = Otp::forPhone($normalized)->ofType($type)->lockForUpdate()->get();

            if ($records->isEmpty()) {
                return ['status' => self::NOT_FOUND, 'otp' => null, 'attempts_left' => $maxAttempts];
            }

            $match = $records->first(
                fn (Otp $otp) => ! $otp->isExpired()
                    && ! $otp->hasExhaustedAttempts()
                    && hash_equals($otp->code, $code)
            );

            if ($match) {
                $match->forceFill([
                    'verified' => true,
                    'verified_at' => Carbon::now(),
                    'attempts' => 0,
                ])->save();

                return ['status' => self::OK, 'otp' => $match, 'attempts_left' => $maxAttempts];
            }

            // No match: burn an attempt on every live candidate so guessing
            // without a `type` is not cheaper than guessing with one.
            $live = $records->reject(fn (Otp $otp) => $otp->isExpired());

            if ($live->isEmpty()) {
                return ['status' => self::EXPIRED, 'otp' => null, 'attempts_left' => $maxAttempts];
            }

            $live->each(function (Otp $otp) {
                $otp->increment('attempts');
            });

            if ($live->every(fn (Otp $otp) => $otp->hasExhaustedAttempts())) {
                return ['status' => self::TOO_MANY_ATTEMPTS, 'otp' => null, 'attempts_left' => 0];
            }

            return [
                'status' => self::INVALID,
                'otp' => null,
                'attempts_left' => max(0, $maxAttempts - (int) $live->max('attempts')),
            ];
        });
    }

    /**
     * Redeem a verified OTP for a downstream action (register / reset).
     * Single use: the row is removed so the same verification cannot authorise
     * a second account.
     */
    public function consume(string $phone, string $type): bool
    {
        return DB::transaction(function () use ($phone, $type) {
            $otp = Otp::forPhone($phone)->ofType($type)->lockForUpdate()->first();

            if (! $otp || ! $otp->isFreshlyVerified()) {
                return false;
            }

            $otp->delete();

            return true;
        });
    }

    /**
     * Read-only variant for callers that must validate before committing other
     * work; pair it with consume() inside the same transaction.
     */
    public function isVerified(string $phone, string $type): bool
    {
        $otp = Otp::forPhone($phone)->ofType($type)->first();

        return $otp !== null && $otp->isFreshlyVerified();
    }

    /**
     * Audiences to verify for a bypassed number that has no stored record.
     */
    private function audiencesFor(string $normalized): array
    {
        $existing = Otp::forPhone($normalized)->pluck('user_type')->filter()->unique()->values()->all();

        return $existing !== [] ? $existing : Otp::TYPES;
    }

    /**
     * updateOrCreate on a unique key races under concurrent requests; retry
     * once on the duplicate-key error instead of returning a 500.
     */
    private function upsert(string $normalized, string $type, array $attributes): Otp
    {
        $key = ['phone' => $normalized, 'user_type' => $type];

        try {
            return Otp::updateOrCreate($key, $attributes);
        } catch (QueryException $e) {
            if (! $this->isDuplicateKey($e)) {
                throw $e;
            }

            return tap(Otp::where($key)->firstOrFail())->update($attributes);
        }
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        return in_array((string) ($e->errorInfo[1] ?? ''), ['1062', '19'], true)
            || str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }
}
