<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\Provider;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class VendorAuthController extends Controller
{
    public function __construct(protected OtpService $otpService)
    {
    }

    /**
     * Register a new vendor.
     *
     * The phone is normalised BEFORE validation and before the OTP lookup so
     * every format the app may post resolves to the same `otps` row.
     */
    public function register(Request $request): JsonResponse
    {
        $request->merge(['phone' => PhoneNumber::normalize($request->input('phone'))]);

        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:providers',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|string|max:20|unique:providers',
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

        $phone = $request->input('phone');

        // Verify + create + burn the OTP atomically. `status` is forced to
        // pending here so a client-supplied field can never self-approve.
        try {
            $vendor = DB::transaction(function () use ($request, $phone) {
                if (! $this->otpService->consume($phone, Otp::TYPE_VENDOR)) {
                    return null;
                }

                return Provider::create([
                    'car_name' => $request->input('car_name'),
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'phone' => $phone,
                    'status' => 'pending', // default status
                    'phone_verified_at' => now(),
                    'bio' => $request->input('bio'),
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'address' => $request->input('address'),
                    'avatar' => $request->input('avatar'),
                    'provider_banner' => $request->input('provider_banner'),
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Two concurrent registrations for the same phone/email.
            return response()->json([
                'status' => false,
                'message' => 'رقم الهاتف مستخدم بالفعل',
            ], 422);
        }

        if ($vendor === null) {
            return response()->json([
                'status' => false,
                'message' => 'يجب التحقق من رقم الهاتف أولاً',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم التسجيل بنجاح',
            'data' => $vendor,
        ], 201);
    }

    /*********************************************************************************/
    public function login(Request $request): JsonResponse
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

        $login = (string) $request->input('login');
        $normalizedLogin = PhoneNumber::normalize($login);
        // Only treat the input as a phone when it plausibly is one, so an
        // e-mail's stray digits can never match someone else's phone column.
        $looksLikePhone = ! str_contains($login, '@') && strlen($normalizedLogin) >= 9;

        // Try to find provider by email or by phone in any accepted format.
        $provider = Provider::where('email', $login)
            ->orWhere('phone', $login)
            ->when($looksLikePhone, fn ($query) => $query->orWhere('phone', $normalizedLogin))
            ->first();

        if (! $provider || ! Hash::check($request->input('password'), $provider->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'بيانات تسجيل الدخول غير صحيحة',
            ], 401);
        }

        // Check account status
        if (! $provider->isActive()) {
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
    public function getVendorProfile(): JsonResponse
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
    public function updateProfile(Request $request, $id): JsonResponse
    {
        $provider = auth()->user();

        // A vendor may only edit their own profile. The route is behind
        // auth:sanctum but used to trust the {id} in the URL, so any
        // authenticated vendor could overwrite any other provider's record.
        if (! $provider || (int) $provider->id !== (int) $id) {
            return response()->json(['message' => 'غير مصرح لك بتعديل هذا الحساب'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'car_name' => 'sometimes|nullable|string|max:255',
            'bio' => 'sometimes|nullable|string',
            'address' => 'sometimes|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'avatar' => 'sometimes|nullable|string',
            'provider_banner' => 'sometimes|nullable|string',
            'is_open' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // Only the whitelisted attributes above are written: `status`,
        // `approved_at`, `phone`, `phone_verified_at`, `email` and `password`
        // are all mass-assignable on the model, so passing $request->all()
        // here let a vendor self-approve and bypass phone verification.
        $provider->update($validator->validated());

        return response()->json([
            'message' => 'تم تحديث البيانات بنجاح',
            'provider' => $provider->fresh(),
        ]);
    }

    /***************************************************************************************/
    // Logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }
}
