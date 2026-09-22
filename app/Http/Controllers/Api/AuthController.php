<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Services\ForJawalyService;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        protected ForJawalyService $forJawalyService,
        protected OtpService $otpService,
    ) {
    }

    /**
     * POST /api/auth/send-otp
     *
     * Body: phone (any accepted format), type (client|vendor)
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'type' => 'required|in:' . implode(',', Otp::TYPES),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        $normalizedPhone = PhoneNumber::normalize($request->input('phone'));

        if ($normalizedPhone === '') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number.',
            ], 400);
        }

        $result = $this->otpService->issue($normalizedPhone, $request->input('type'));

        if ($result['status'] === OtpService::COOLDOWN) {
            return response()->json([
                'success' => false,
                'message' => 'An OTP was already sent. Please wait before requesting another one.',
                'phone' => $normalizedPhone,
                'retry_after' => $result['retry_after'],
            ], 429)->header('Retry-After', $result['retry_after']);
        }

        // Test numbers never touch the gateway; the record is already stored
        // with the fixed code, so verify + register behave exactly as they do
        // for a real number.
        if ($result['bypassed']) {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully (Bypassed Number)',
                'phone' => $normalizedPhone,
                'code' => $result['code'],
            ], 200);
        }

        try {
            $gatewayResponse = $this->forJawalyService->sendSMS(
                PhoneNumber::forSms($normalizedPhone),
                "Your account verification code is: {$result['code']}"
            );

            if ((int) ($gatewayResponse['code'] ?? 0) === 200) {
                return response()->json(array_filter([
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'phone' => $normalizedPhone,
                    'code' => config('otp.expose_code') ? $result['code'] : null,
                ], static fn ($value) => $value !== null), 200);
            }

            // The code was stored but never delivered: drop it so the user is
            // not left with an unusable pending OTP blocking the cooldown.
            $result['otp']?->delete();

            Log::warning('OTP SMS rejected by gateway', [
                'phone' => PhoneNumber::mask($normalizedPhone),
                'response' => $gatewayResponse,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS via provider',
                'phone' => $normalizedPhone,
                'sms_error' => $gatewayResponse['message'] ?? 'SMS Gateway Error',
            ], 400);
        } catch (\Throwable $e) {
            $result['otp']?->delete();

            Log::error('OTP SMS exception', [
                'phone' => PhoneNumber::mask($normalizedPhone),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SMS service exception occurred',
                'phone' => $normalizedPhone,
            ], 500);
        }
    }

    /**
     * POST /api/auth/verify-otp
     *
     * Body: phone, otp (4 digits), type (optional: client|vendor)
     *
     * On success the `otps` row is persisted with verified = true and the
     * audience in user_type — for bypassed numbers too. Registration reads
     * exactly that row, so skipping this write is what used to produce
     * "يجب التحقق من رقم الهاتف أولاً".
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'otp' => 'required|digits:4',
            'type' => 'nullable|in:' . implode(',', Otp::TYPES),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        $normalizedPhone = PhoneNumber::normalize($request->input('phone'));

        $result = $this->otpService->verify(
            $normalizedPhone,
            (string) $request->input('otp'),
            $request->input('type')
        );

        return match ($result['status']) {
            OtpService::OK => response()->json([
                'success' => true,
                'message' => 'Phone number verified successfully.',
                'phone' => $normalizedPhone,
                'user_type' => $result['otp']?->user_type,
                'isVerified' => true,
            ], 200),

            OtpService::NOT_FOUND => response()->json([
                'success' => false,
                'message' => 'Phone number not found.',
            ], 404),

            OtpService::EXPIRED => response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 400),

            OtpService::TOO_MANY_ATTEMPTS => response()->json([
                'success' => false,
                'message' => 'Too many incorrect attempts. Please request a new OTP.',
            ], 429),

            default => response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
                'attempts_left' => $result['attempts_left'],
            ], 400),
        };
    }
}
