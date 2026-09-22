<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Services\ForJawalyService;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Shared phone-OTP password reset for clients and vendors.
 *
 * The client and vendor copies of this flow had drifted apart (different
 * bypass lists, one of them unreachable because the bypass code was 6 digits
 * while validation demanded 4, and neither wrote `user_type`). Keeping one
 * implementation is what stops that happening again.
 */
trait ResetsPasswordViaOtp
{
    abstract protected function otpUserType(): string;

    abstract protected function findAccount(string $normalizedPhone): ?Model;

    abstract protected function accountMissingMessage(): string;

    /**
     * POST .../reset-password/otp
     */
    public function resetPasswordOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        $phone = PhoneNumber::normalize($request->input('phone'));

        if ($phone === '' || ! $this->findAccount($phone)) {
            return response()->json([
                'success' => false,
                'message' => $this->accountMissingMessage(),
            ], 404);
        }

        $result = app(OtpService::class)->issue($phone, $this->otpUserType());

        if ($result['status'] === OtpService::COOLDOWN) {
            return response()->json([
                'success' => false,
                'message' => 'An OTP was already sent. Please wait before requesting another one.',
                'phone' => $phone,
                'retry_after' => $result['retry_after'],
            ], 429)->header('Retry-After', $result['retry_after']);
        }

        // Test numbers skip the gateway but still get a stored, verifiable row.
        if ($result['bypassed']) {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully (Bypassed Number)',
                'phone' => $phone,
                'code' => $result['code'],
            ], 200);
        }

        try {
            $gatewayResponse = app(ForJawalyService::class)->sendSMS(
                PhoneNumber::forSms($phone),
                "Your account verification code is: {$result['code']}"
            );

            if ((int) ($gatewayResponse['code'] ?? 0) === 200) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'phone' => $phone,
                ], 200);
            }

            $result['otp']?->delete();

            Log::warning('Reset OTP rejected by gateway', [
                'phone' => PhoneNumber::mask($phone),
                'user_type' => $this->otpUserType(),
                'response' => $gatewayResponse,
            ]);

            return response()->json([
                'success' => false,
                'message' => $gatewayResponse['message'] ?? 'Error occurred while sending OTP',
            ], 500);
        } catch (\Throwable $e) {
            $result['otp']?->delete();

            Log::error('Reset OTP exception', [
                'phone' => PhoneNumber::mask($phone),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again later.',
            ], 500);
        }
    }

    /**
     * POST .../reset-password/verify-otp
     */
    public function verifyResetPasswordOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'otp' => 'required|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        $phone = PhoneNumber::normalize($request->input('phone'));

        $result = app(OtpService::class)->verify(
            $phone,
            (string) $request->input('otp'),
            $this->otpUserType()
        );

        return match ($result['status']) {
            OtpService::OK => response()->json([
                'success' => true,
                'message' => 'Phone number verified successfully.',
                'phone' => $phone,
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

    /**
     * PUT .../reset-password
     *
     * Requires a freshly verified OTP for this phone. Without that check the
     * endpoint was a full account takeover: anyone who knew a phone number
     * could set that account's password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $phone = PhoneNumber::normalize($request->input('phone'));
        $otpService = app(OtpService::class);

        $account = DB::transaction(function () use ($otpService, $phone, $request) {
            if (! $otpService->consume($phone, $this->otpUserType())) {
                return false;
            }

            $account = $this->findAccount($phone);

            if (! $account) {
                return null;
            }

            $account->password = Hash::make($request->input('new_password'));
            $account->save();

            // Force re-authentication everywhere after a password change.
            if (method_exists($account, 'tokens')) {
                $account->tokens()->delete();
            }

            return $account;
        });

        if ($account === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'يجب التحقق من رقم الهاتف أولاً',
            ], 422);
        }

        if ($account === null) {
            return response()->json([
                'status' => 'error',
                'message' => $this->accountMissingMessage(),
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successfully',
        ], 200);
    }
}
