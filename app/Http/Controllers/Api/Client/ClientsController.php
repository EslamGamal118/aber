<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class ClientsController extends Controller
{
    /**
     * Register a new client.
     */
    public function register(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|unique:clients',
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

        // Check if phone is verified via OTP
        $otpRecord = Otp::where('phone', $request->phone)
            ->where('user_type', 'client')
            ->where('verified', true)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status' => false,
                'message' => 'يجب التحقق من رقم الهاتف أولاً',
            ], 422);
        }

        // Create client
        $client = Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'phone_verified_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم التسجيل بنجاح',
            'data' => $client
        ], 201);
    }

    /*************************************************************************************/
    public function login(Request $request)
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

        // Try to find client by email or phone
        $client = Client::where('email', $request->login)
            ->orWhere('phone', $request->login)
            ->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
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
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }
    /***************************************************************************************/
}
