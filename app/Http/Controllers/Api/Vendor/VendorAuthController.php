<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Provider;
use App\Models\Otp;
use App\Services\SmsService;
use Carbon\Carbon;

class VendorAuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:providers',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|unique:providers',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ], [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'يجب أن يكون البريد الإلكتروني صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'يجب أن تكون كلمة المرور على الأقل 6 أحرف',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex' => 'يجب أن يكون رقم الهاتف بصيغة 5XXXXXXXX',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل',
            'address.required' => 'العنوان مطلوب',
            'latitude.required' => 'خط الطول مطلوب',
            'longitude.required' => 'خط العرض مطلوب',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Check if phone is verified via OTP
        $otpRecord = Otp::where('phone', $request->phone)
                    ->where('user_type', 'vendor')
                    ->where('verified', true)
                    ->first();
        
        if (!$otpRecord) {
            return response()->json([
                'status' => false,
                'message' => 'يجب التحقق من رقم الهاتف أولاً',
            ], 422);
        }

        // Create vendor
        $vendor = Provider::create([
            'car_name' => $request->car_name,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => 'pending', // default status
            'phone_verified_at' => now(),
            'bio' => $request->bio,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
            'avatar' => $request->avatar ?? null,
            'provider_banner' => $request->provider_banner ?? null
        ]);
        
        $vendor->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم التسجيل بنجاح',
            'data' => $vendor
        ], 201);
    }

    /*********************************************************************************/
public function login(Request $request)
{
        // Validate input
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

        // Try to find provider by email or phone
        $provider = Provider::where('email', $request->login)
            ->orWhere('phone', $request->login)
            ->first();

        if (!$provider || !Hash::check($request->password, $provider->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'بيانات تسجيل الدخول غير صحيحة',
            ], 401);
        }

        // Check account status
        if (!$provider->isActive()) {
            return response()->json([
                'status' => false,
                'message' => 'الحساب غير مفعل، يرجى الانتظار حتى تتم الموافقة على حسابك',
            ], 403);
        }

        // Create a Sanctum token
        $token = $provider->createToken('provider_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'data' => $provider,
        ]);
}

/***************************************************************************************/
// Get Current Vendor Profile
public function getVendorProfile()
{
    $provider = auth()->user();

    return response()->json([
        'status' => 'success',
        'message' => 'تم التحقق من الحساب بنجاح',
        'data' => $provider,
    ]);
}
/***************************************************************************************/
// Update Profile
public function updateProfile(Request $request, $id)
{
    $provider = Provider::find($id);
    if (!$provider) {
        return response()->json(['message' => 'مقدم الخدمة غير موجود'], 404);
    }
    
    $provider->update($request->all());
    return response()->json(['message' => 'تم تحديث البيانات بنجاح', 'provider' => $provider]); 
}
/***************************************************************************************/
// Logout
public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'تم تسجيل الخروج بنجاح',
    ]);
}

}