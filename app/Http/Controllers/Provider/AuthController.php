<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\PhoneVerification;
use App\Models\Provider;
use App\Services\ForJawalyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $forJawalyService;

    public function __construct(ForJawalyService $forJawalyService)
    {
        $this->forJawalyService = $forJawalyService;
    }

    /**
     * Show phone verification form
     */
    public function showPhoneForm()
    {
        return view('provider.auth.phone');
    }

    /**
     * Show OTP verification form
     */
    public function showVerifyForm(Request $request)
    {
        $phone = $request->session()->get('phone');
        
        if (!$phone) {
            return redirect()->route('provider.phone');
        }
        
        return view('provider.auth.verify', compact('phone'));
    }

    /**
     * Show registration form
     */
    public function showRegisterForm(Request $request)
    {
        $phone = $request->session()->get('phone');
        
        if (!$phone || !$request->session()->get('phone_verified')) {
            return redirect()->route('provider.phone');
        }
        
        return view('provider.auth.register', compact('phone'));
    }

    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('provider.auth.login');
    }

    /**
     * Send OTP to phone number
     */
    public function sendOtp(Request $request)
    {
        // Validate the phone number - قبول أرقام تبدأ بـ 5 للأرقام السعودية
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^5[0-9]{8}$/',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $phone = $request->phone;
        $verificationCode = rand(100000, 999999);
        $expirationTime = Carbon::now()->addMinutes(10);

        // Check if a provider already exists with this phone
        $provider = Provider::where('phone', $phone)->first();
        
        // Store phone in session
        $request->session()->put('phone', $phone);

        // Check if the phone number already exists in phone verifications
        $phoneRecord = PhoneVerification::where('phone', $phone)->first();

        if ($phoneRecord) {
            // Update the verification code
            $phoneRecord->verification_code = Hash::make($verificationCode);
            $phoneRecord->verified_at = null;
            $phoneRecord->current_code_expired_at = $expirationTime;
            $phoneRecord->save();
        } else {
            // Create a new phone verification record
            PhoneVerification::create([
                'phone' => $phone,
                'verification_code' => Hash::make($verificationCode),
                'current_code_expired_at' => $expirationTime,
            ]);
        }

        Log::info("Sending OTP to {$phone} with code {$verificationCode}");

        try {
            // Sending SMS via service
            $result = $this->forJawalyService->sendSMS($phone, "Your verification code is: {$verificationCode}");

            if (isset($result['code']) && $result['code'] === 200) {
                return redirect()->route('provider.verify')->with('success', 'OTP sent successfully');
            } else {
                // تخطي مشكلة الإرسال للاختبار والتطوير
                Log::warning("SMS could not be sent, but proceeding for development: " . json_encode($result));
                // حفظ رمز التحقق في الجلسة لاستخدامه في التطوير
                $request->session()->put('dev_verification_code', $verificationCode);
                return redirect()->route('provider.verify')
                    ->with('warning', 'Development mode: Check log for OTP code. Proceeding with verification.');
            }
        } catch (\Exception $e) {
            Log::error('Error sending OTP: ' . $e->getMessage());
            // تخطي مشكلة الإرسال للاختبار والتطوير
            $request->session()->put('dev_verification_code', $verificationCode);
            return redirect()->route('provider.verify')
                ->with('warning', 'Development mode: Check log for OTP code. Proceeding with verification.');
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        // Validate the OTP
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $phone = $request->session()->get('phone');
        
        if (!$phone) {
            return redirect()->route('provider.phone')->with('error', 'Phone number not found.');
        }

        // للتطوير فقط: استخدام رمز التحقق من الجلسة
        $devCode = $request->session()->get('dev_verification_code');
        if ($devCode && $devCode == $request->otp) {
            // Mark the phone as verified for development
            $phoneRecord = PhoneVerification::where('phone', $phone)->first();
            if ($phoneRecord) {
                $phoneRecord->verified_at = now();
                $phoneRecord->save();
            } else {
                PhoneVerification::create([
                    'phone' => $phone,
                    'verification_code' => Hash::make($request->otp),
                    'verified_at' => now(),
                ]);
            }
            
            // Store verification status in session
            $request->session()->put('phone_verified', true);
            $request->session()->forget('dev_verification_code');

            // Check if a provider already exists with this phone
            $provider = Provider::where('phone', $phone)->first();
            
            if ($provider) {
                // If provider exists, go to login
                return redirect()->route('provider.login')
                    ->with('success', 'Phone number verified. Please login with your credentials.');
            } else {
                // If new user, go to registration
                return redirect()->route('provider.register')
                    ->with('success', 'Phone number verified. Please complete your registration.');
            }
        }

        // الكود الأصلي للتحقق
        $phoneRecord = PhoneVerification::where('phone', $phone)->first();

        if (!$phoneRecord) {
            return back()->with('error', 'Phone number not found.');
        }

        // Check if the OTP has expired
        if ($phoneRecord->isExpired()) {
            return back()->with('error', 'OTP has expired. Please request a new one.');
        }

        // Check if the OTP matches the hashed verification code
        if (Hash::check($request->otp, $phoneRecord->verification_code)) {
            // Mark the phone as verified
            $phoneRecord->verified_at = now();
            $phoneRecord->save();
            
            // Store verification status in session
            $request->session()->put('phone_verified', true);

            // Check if a provider already exists with this phone
            $provider = Provider::where('phone', $phone)->first();
            
            if ($provider) {
                // If provider exists, go to login
                return redirect()->route('provider.login')
                    ->with('success', 'Phone number verified. Please login with your credentials.');
            } else {
                // If new user, go to registration
                return redirect()->route('provider.register')
                    ->with('success', 'Phone number verified. Please complete your registration.');
            }
        } else {
            return back()->with('error', 'Invalid OTP. Please try again.');
        }
    }

    /**
     * Register a new provider
     */
    public function register(Request $request)
    {
        $phone = $request->session()->get('phone');
        $phoneVerified = $request->session()->get('phone_verified');
        
        if (!$phone || !$phoneVerified) {
            return redirect()->route('provider.phone')
                ->with('error', 'Phone verification is required.');
        }

        // Validate the registration data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:providers',
            'password' => 'required|string|min:8|confirmed',
            'type' => 'required|in:personal,company',
            'id_card' => 'required_if:type,personal|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'commercial_register' => 'required_if:type,company|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Handle file uploads
            $idCardPath = null;
            $commercialRegisterPath = null;
            
            if ($request->type === 'personal' && $request->hasFile('id_card')) {
                $idCardPath = $request->file('id_card')->store('providers/id_cards', 'public');
            }
            
            if ($request->type === 'company' && $request->hasFile('commercial_register')) {
                $commercialRegisterPath = $request->file('commercial_register')->store('providers/commercial_registers', 'public');
            }

            // Create the provider
            $provider = Provider::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $phone,
                'password' => Hash::make($request->password),
                'type' => $request->type,
                'id_card' => $idCardPath,
                'commercial_register' => $commercialRegisterPath,
                'status' => 'pending',
                'phone_verified_at' => now(),
            ]);

            // Clear session data
            $request->session()->forget(['phone', 'phone_verified']);

            // Log in the provider
            Auth::guard('provider')->login($provider);
            
            return redirect()->route('provider.dashboard')
                ->with('success', 'Registration successful! Your account is pending approval.');
        } catch (\Exception $e) {
            Log::error('Provider registration error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred during registration. Please try again.')->withInput();
        }
    }

    /**
     * Login a provider
     */
    public function login(Request $request)
    {
        // Validate login credentials
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',  // Can be email or phone
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [
            $loginField => $request->login,
            'password' => $request->password,
        ];

        // Attempt to log in
        if (Auth::guard('provider')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            
            return redirect()->intended(route('provider.dashboard'));
        }

        // If login fails
        return back()
            ->withInput($request->only('login', 'remember'))
            ->withErrors(['login' => 'These credentials do not match our records.']);
    }

    /**
     * Logout a provider
     */
    public function logout(Request $request)
    {
        Auth::guard('provider')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('provider.login');
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $phone = $request->session()->get('phone');

        if (!$phone) {
            return redirect()->route('provider.phone')
                ->with('error', 'Phone number not found.');
        }

        $verificationCode = rand(100000, 999999);
        $expirationTime = Carbon::now()->addMinutes(10);

        // Update the verification code
        $phoneRecord = PhoneVerification::where('phone', $phone)->first();
        
        if ($phoneRecord) {
            $phoneRecord->verification_code = Hash::make($verificationCode);
            $phoneRecord->verified_at = null;
            $phoneRecord->current_code_expired_at = $expirationTime;
            $phoneRecord->save();
        } else {
            // Should not reach here, but just in case
            PhoneVerification::create([
                'phone' => $phone,
                'verification_code' => Hash::make($verificationCode),
                'current_code_expired_at' => $expirationTime,
            ]);
        }

        Log::info("Resending OTP to {$phone} with code {$verificationCode}");

        try {
            // Sending SMS via service
            $result = $this->forJawalyService->sendSMS($phone, "Your verification code is: {$verificationCode}");

            if (isset($result['code']) && $result['code'] === 200) {
                return back()->with('success', 'OTP resent successfully');
            } else {
                return back()->with('error', $result['message'] ?? 'An error occurred while resending OTP');
            }
        } catch (\Exception $e) {
            Log::error('Error resending OTP: ' . $e->getMessage());
            return back()->with('error', 'Failed to resend OTP. Please try again later.');
        }
    }
}
