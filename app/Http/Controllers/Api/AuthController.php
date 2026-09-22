<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

    // Send OTP to the user's phone
    public function sendOtp(Request $request)
    {
        // Validate the phone number
        // "type" is stored as otps.user_type and must match what the register
        // endpoints look for (client / vendor).
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
    
        // Skip OTP sending for specific phone numbers
        if ($phone == '01121926996' || $phone == '01092841138' || $phone == '01094963620') {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'phone' => $phone,
            ], 200);
        }
    
        // Generate a random 4-digit verification code
        $verificationCode = rand(1000, 9999);
        $expirationTime = Carbon::now()->addMinutes(10);
    
        // Check if the phone exists, then update or create a new phone record
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
    
        // Bypass SMS sending in local environment or fallback on service error
        try {
            if (app()->environment('local')) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP generated successfully (Local Testing Mode)',
                    'phone' => $phone,
                    'code' => $verificationCode
                ], 200);
            }

            $result = $this->forJawalyService->sendSMS($phone, "Your account verification code is: {$verificationCode}");
    
            if (isset($result['code']) && $result['code'] === 200) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'phone' => $phone,
                    'code' => $verificationCode
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP generated successfully (SMS service failed/bypassed)',
                    'phone' => $phone,
                    'code' => $verificationCode,
                    'sms_error' => $result['message'] ?? 'SMS Gateway Error'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'message' => 'OTP generated successfully (SMS Exception)',
                'phone' => $phone,
                'code' => $verificationCode,
                'error' => $e->getMessage()
            ], 200);
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
    
        $phoneRecord = Otp::where('phone', $request->phone)->first();
    
        $otp = $request->otp;
        $phone = $request->phone;
    
        if ($phone == '01121926996' || $phone == '01092841138' || $phone == '01094963620') {
            if ($otp == '123456') {
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