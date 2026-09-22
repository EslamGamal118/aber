<?php

namespace App\Support;

/**
 * Single source of truth for phone-number handling.
 *
 * Every layer of the OTP pipeline (send, verify, register, reset, delete) must
 * key on the SAME string, otherwise a number sent as "+966500000001" and
 * verified as "0500000001" silently becomes two different records and
 * registration fails with "Phone number not verified".
 */
class PhoneNumber
{
    /**
     * Canonical storage format: Saudi national format, "05XXXXXXXX".
     *
     * Accepts and collapses "+966 50 000 0001", "00966500000001",
     * "966500000001", "500000001", "0500000001" to one value.
     * Non-Saudi numbers are returned digits-only and otherwise untouched.
     */
    public static function normalize(?string $phone): string
    {
        // Drop "+", spaces, dashes, parentheses and any Arabic-Indic digits.
        $digits = preg_replace('/\D+/', '', strtr((string) $phone, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]));

        if ($digits === '') {
            return '';
        }

        // 00966XXXXXXXXX -> 966XXXXXXXXX
        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 2);
        }

        // 966XXXXXXXXX -> 0XXXXXXXXX
        if (str_starts_with($digits, '966') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 3);
        }

        // 5XXXXXXXX -> 05XXXXXXXX (mobile without the trunk prefix)
        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /**
     * Format handed to the SMS gateway. 4Jawaly expects KSA numbers in
     * international form without a leading "+": 9665XXXXXXXX.
     *
     * Set OTP_SMS_INTERNATIONAL=false to send the number exactly as stored
     * (the pre-existing behaviour) if the gateway account is configured for
     * national format.
     */
    public static function forSms(?string $phone): string
    {
        $normalized = self::normalize($phone);

        if (! config('otp.sms_international', true)) {
            return $normalized;
        }

        if (self::isSaudiMobile($normalized)) {
            return '966' . substr($normalized, 1);
        }

        return $normalized;
    }

    public static function isSaudiMobile(?string $phone): bool
    {
        return (bool) preg_match('/^05\d{8}$/', self::normalize($phone));
    }

    /**
     * Test numbers that skip the SMS gateway and accept the fixed bypass code.
     * Compared on the NORMALIZED value so "+966500000001" is recognised too.
     */
    public static function isBypassed(?string $phone): bool
    {
        $normalized = self::normalize($phone);

        if ($normalized === '') {
            return false;
        }

        $bypassed = array_map(
            static fn ($number) => self::normalize($number),
            (array) config('otp.bypassed_numbers', [])
        );

        return in_array($normalized, $bypassed, true);
    }

    /**
     * Mask for logs: 05XXXXX901 -> 05****9901.
     */
    public static function mask(?string $phone): string
    {
        $normalized = self::normalize($phone);
        $length = strlen($normalized);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($normalized, 0, 2) . str_repeat('*', $length - 6) . substr($normalized, -4);
    }
}
