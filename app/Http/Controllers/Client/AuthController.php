<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PhoneVerification;
use App\Services\ForJawalyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        return view('client.auth.phone');
    }

    /**
     * Show OTP verification form
     */
    public function showVerifyForm(Request $request)
    {
        $phone = $request->session()->get('phone');
        
        if (!$phone) {
            return redirect()->route('client.phone');
        }
        
        return view('client.auth.verify', compact('phone'));
    }

    /**
     * Show registration form
     */
    public function showRegisterForm(Request $request)
    {
        $phone = $request->session()->get('phone');
        
        if (!$phone || !$request->session()->get('phone_verified')) {
            return redirect()->route('client.phone');
        }
        
        return view('client.auth.register', compact('phone'));
    }

    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('client.auth.login');
    }

    /**
     * Send OTP to phone number
     */
    public function sendOtp(Request $request)
    {
        // Validate the phone number
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^5[0-9]{8}$/',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $phone = $request->phone;
        $verificationCode = rand(100000, 999999);
        $expirationTime = Carbon::now()->addMinutes(10);

        // Check if a client already exists with this phone
        $client = Client::where('phone', $phone)->first();
        
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

        Log::info("Sending OTP to client {$phone} with code {$verificationCode}");

        try {
            // Sending SMS via service
            $result = $this->forJawalyService->sendSMS($phone, "Your verification code is: {$verificationCode}");

            if (isset($result['code']) && $result['code'] === 200) {
                return redirect()->route('client.verify')->with('success', 'OTP sent successfully');
            } else {
                // تخطي مشكلة الإرسال للاختبار والتطوير
                Log::warning("SMS could not be sent to client, but proceeding for development: " . json_encode($result));
                // حفظ رمز التحقق في الجلسة لاستخدامه في التطوير
                $request->session()->put('dev_verification_code', $verificationCode);
                return redirect()->route('client.verify')
                    ->with('warning', 'Development mode: Check log for OTP code. Proceeding with verification.');
            }
        } catch (\Exception $e) {
            Log::error('Error sending OTP to client: ' . $e->getMessage());
            // تخطي مشكلة الإرسال للاختبار والتطوير
            $request->session()->put('dev_verification_code', $verificationCode);
            return redirect()->route('client.verify')
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
            return redirect()->route('client.phone')->with('error', 'Phone number not found.');
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

            // Check if a client already exists with this phone
            $client = Client::where('phone', $phone)->first();
            
            if ($client) {
                // If client exists, go to login
                return redirect()->route('client.login')
                    ->with('success', 'Phone number verified. Please login with your credentials.');
            } else {
                // If new user, go to registration
                return redirect()->route('client.register')
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

            // Check if a client already exists with this phone
            $client = Client::where('phone', $phone)->first();
            
            if ($client) {
                // If client exists, go to login
                return redirect()->route('client.login')
                    ->with('success', 'Phone number verified. Please login with your credentials.');
            } else {
                // If new user, go to registration
                return redirect()->route('client.register')
                    ->with('success', 'Phone number verified. Please complete your registration.');
            }
        } else {
            return back()->with('error', 'Invalid OTP. Please try again.');
        }
    }

    /**
     * Register a new client
     */
    public function register(Request $request)
    {
        $phone = $request->session()->get('phone');
        $phoneVerified = $request->session()->get('phone_verified');
        
        if (!$phone || !$phoneVerified) {
            return redirect()->route('client.phone')
                ->with('error', 'Phone verification is required.');
        }

        // Validate the registration data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients',
            'password' => 'required|string|min:8|confirmed',
            'gender' => 'nullable|in:male,female',
            'birthdate' => 'nullable|date|before:today',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Create the client
            $client = Client::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $phone,
                'password' => Hash::make($request->password),
                'gender' => $request->gender,
                'birthdate' => $request->birthdate,
                'phone_verified_at' => now(),
                'is_active' => true,
            ]);

            // Clear session data
            $request->session()->forget(['phone', 'phone_verified']);

            // Log in the client
            Auth::guard('client')->login($client);
            
            return redirect()->route('client.dashboard')
                ->with('success', 'Registration successful! Welcome to our platform.');
        } catch (\Exception $e) {
            Log::error('Client registration error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred during registration. Please try again.')->withInput();
        }
    }

    /**
     * Login the client
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('phone', 'password');
        $remember = $request->filled('remember');

        if (Auth::guard('client')->attempt($credentials, $remember)) {
            $client = Auth::guard('client')->user();
            
            // Check if client is active
            if (!$client->is_active) {
                Auth::guard('client')->logout();
                return back()->with('error', 'Your account has been deactivated. Please contact support.');
            }
            
            return redirect()->intended(route('client.dashboard'));
        }

        return back()->with('error', 'Invalid login credentials.')->withInput();
    }

    /**
     * Logout the client
     */
    public function logout(Request $request)
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('client.login');
    }
}
