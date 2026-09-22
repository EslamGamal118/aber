<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\ForJawalyService;
use Carbon\Carbon;

class AuthController extends Controller
{
    protected $forJawalyService;

    public function __construct(ForJawalyService $forJawalyService)
    {
        $this->forJawalyService = $forJawalyService;
    }

    // دالة توحيد صيغة الرقم لتكون موحدة في الإرسال والتحقق
    private function normalizePhoneNumber($phone)
    {
        $normalized = preg_replace('/^\+?966/', '0', trim($phone));
        if (!str_starts_with($normalized, '0') && strlen($normalized) == 9) {
            $normalized = '0' . $normalized;
        }
        return $normalized;
    }

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'type' => 'required|in:' . implode(',', Otp::TYPES),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $phone = $request->phone;
        $normalizedPhone = $this->normalizePhoneNumber($phone);

        // قائمة الأرقام المستثناة الموحدة
        $bypassedPhones = [
            '0500000001',
            '0500000002',
            '0500000003',
            '0500000004',
        ];

        $isBypassed = in_array($normalizedPhone, $bypassedPhones);
        $verificationCode = $isBypassed ? 1234 : rand(1000, 9999);
        $expirationTime = Carbon::now()->addMinutes(10);

        // حفظ الرقم بصيغته الأصلية أو الموحدة حسب رغبتك (هنا نحفظ المدخل أو الموحد)
        $phoneRecord = Otp::updateOrCreate(
            ['phone' => $phone],
            [
                'code' => $verificationCode,
                'verified' => 0,
                'expires_at' => $expirationTime,
                'user_type' => $request->type,
            ]
        );

        Log::info("Sending OTP to {$phone} with code {$verificationCode}");

        if ($isBypassed) {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully (Bypassed Number)',
                'phone' => $phone,
                'code' => 1234,
            ], 200);
        }

        try {
            $result = $this->forJawalyService->sendSMS($phone, "Your account verification code is: {$verificationCode}");

            if (isset($result['code']) && $result['code'] === 200) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'phone' => $phone,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send SMS via provider',
                    'phone' => $phone,
                    'sms_error' => $result['message'] ?? 'SMS Gateway Error'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMS service exception occurred',
                'phone' => $phone,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /******************************************************************************************/
    // Verify the OTP sent to the user's phone
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:4',
            'phone' => 'required',
        ]);

        $otp = $request->otp;
        $phone = $request->phone;
        $normalizedPhone = $this->normalizePhoneNumber($phone);

        // الأرقام المستثناة للتحقق (يجب أن تتطابق مع دالة sendOtp وبـ 4 أرقام 1234)
        $bypassedPhones = [
            '0500000001',
            '0500000002',
            '0500000003',
            '0500000004',
        ];

        if (in_array($normalizedPhone, $bypassedPhones)) {
            if ($otp == '1234') {
                return response()->json([
                    'success' => true,
                    'message' => 'Phone number verified successfully.',
                    'phone' => $phone,
                    'isVerified' => true,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP. Please try again.',
                ], 400);
            }
        }

        $phoneRecord = Otp::where('phone', $phone)->first();

        if (!$phoneRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not found.',
            ], 404);
        }

        if (Carbon::now()->greaterThan($phoneRecord->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 400);
        }

        // Direct comparison of OTP
        if ($otp == $phoneRecord->code) {
            $phoneRecord->verified = true;
            $phoneRecord->save();

            return response()->json([
                'success' => true,
                'message' => 'Phone number verified successfully.',
                'phone' => $phoneRecord->phone,
                'isVerified' => true,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 400);
        }
    }
}