<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Structured logger for the Al Rajhi payment flow
 */
class PaymentLogger
{
    /**
     * Generate correlation ID from order ID or UUID
     */
    public static function correlationId(?int $orderId = null): string
    {
        return $orderId ? "order_{$orderId}" : 'pay_' . Str::uuid()->toString();
    }

    /**
     * Log info with structured context
     */
    public static function info(string $message, array $context = []): void
    {
        Log::info($message, self::enrichContext($context));
    }

    /**
     * Log error with structured context
     */
    public static function error(string $message, array $context = []): void
    {
        Log::error($message, self::enrichContext($context));
    }

    /**
     * Enrich context with standard fields
     */
    private static function enrichContext(array $context): array
    {
        return array_merge([
            'service' => 'alrajhi',
            'timestamp' => now()->toIso8601String(),
        ], array_filter($context));
    }

    /**
     * Calculate hash of payload (for logging without exposing full data)
     */
    public static function hashPayload(string $payload): string
    {
        return hash('sha256', $payload);
    }
}
