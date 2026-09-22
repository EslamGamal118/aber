<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bypassed (test) numbers
    |--------------------------------------------------------------------------
    |
    | These numbers never hit the SMS gateway; they always receive
    | `bypass_code`. They are stored in the `otps` table exactly like a real
    | number so that verify + register behave identically for them.
    |
    | Values are normalised through App\Support\PhoneNumber before comparison,
    | so any accepted input format matches.
    |
    */
    'bypassed_numbers' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('OTP_BYPASSED_NUMBERS', '0500000001,0500000002,0500000003,0500000004'))
    ))),

    'bypass_code' => (string) env('OTP_BYPASS_CODE', '1234'),

    /*
    |--------------------------------------------------------------------------
    | Lifetimes and limits
    |--------------------------------------------------------------------------
    |
    | ttl_minutes         how long an issued code stays usable
    | verification_ttl    how long a *verified* OTP may be redeemed by register
    |                     / reset-password before it must be re-verified
    | max_attempts        wrong-code submissions allowed per issued code
    | resend_cooldown     seconds between two send-otp calls for one number
    |
    */
    'ttl_minutes' => (int) env('OTP_TTL_MINUTES', 10),
    'verification_ttl_minutes' => (int) env('OTP_VERIFICATION_TTL_MINUTES', 30),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | SMS gateway number format
    |--------------------------------------------------------------------------
    |
    | true  -> 9665XXXXXXXX (4Jawaly's documented format)
    | false -> send the number exactly as normalised/stored (05XXXXXXXX)
    |
    */
    'sms_international' => (bool) env('OTP_SMS_INTERNATIONAL', true),

    /*
    |--------------------------------------------------------------------------
    | Debug echo
    |--------------------------------------------------------------------------
    |
    | When true, send-otp returns the generated code in the JSON response for
    | NON-bypassed numbers too. Never enable in production.
    |
    */
    'expose_code' => (bool) env('OTP_EXPOSE_CODE', false),

];
