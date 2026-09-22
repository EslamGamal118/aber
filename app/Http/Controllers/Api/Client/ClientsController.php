<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Otp;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClientsController extends Controller
{
    public function __construct(protected OtpService $otpService)
    {
    }

    /**
     * Register a new client.
     *
     * The phone is normalised BEFORE validation and before the OTP lookup, so
     * a number verified as "+966551000009" and registered as "0551000009" is
     * recognised as the same number.
     */
    public function register(Request $request): JsonResponse
    {
        $request->merge(['phone' => PhoneNumber::normalize($request->input('phone'))]);

        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|string|max:20|unique:clients',
        ], [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'يجب أن يكون البريد الإلكتروني صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'يجب أن تكون كلمة المرور على الأقل 6 أحرف',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $phone = $request->input('phone');

        // Check if phone is verified via OTP, then create the account and burn
        // the OTP in one transaction: a half-applied registration would either
        // leave an account without a consumed OTP (reusable to create more) or
        // consume the OTP without an account (user must verify again).
        try {
            $client = DB::transaction(function () use ($request, $phone) {
                if (! $this->otpService->consume($phone, Otp::TYPE_CLIENT)) {
                    return null;
                }

                return Client::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'phone' => $phone,
                    'phone_verified_at' => now(),
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Two concurrent registrations for the same phone/email.
            return response()->json([
                'status' => false,
                'message' => 'رقم الهاتف مستخدم بالفعل',
            ], 422);
        }

        if ($client === null) {
            return response()->json([
                'status' => false,
                'message' => 'يجب التحقق من رقم الهاتف أولاً',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم التسجيل بنجاح',
            'data' => $client,
        ], 201);
    }

    /*************************************************************************************/
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string', // Email or phone
            'password' => 'required|string',
        ], [
            'login.required' => 'البريد الإلكتروني أو رقم الهاتف مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $login = (string) $request->input('login');
        $normalizedLogin = PhoneNumber::normalize($login);
        // Only treat the input as a phone when it plausibly is one, so an
        // e-mail's stray digits can never match someone else's phone column.
        $looksLikePhone = ! str_contains($login, '@') && strlen($normalizedLogin) >= 9;

        // Try to find client by email or by phone in any accepted format.
        $client = Client::where('email', $login)
            ->orWhere('phone', $login)
            ->when($looksLikePhone, fn ($query) => $query->orWhere('phone', $normalizedLogin))
            ->first();

        if (! $client || ! Hash::check($request->input('password'), $client->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'بيانات تسجيل الدخول غير صحيحة',
            ], 401);
        }

        // Generate Sanctum token
        $token = $client->createToken('client_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'data' => $client,
        ]);
    }

    /**************************************************************************************/
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }
    /***************************************************************************************/
}
