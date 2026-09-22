<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Apply provider authentication middleware
        $this->middleware('auth:provider');
    }

    /**
     * Show the provider dashboard
     */
    public function index()
    {
        $provider = Auth::guard('provider')->user();
        return view('provider.dashboard.index', compact('provider'));
    }

    /**
     * Show provider profile
     */
    public function showProfile()
    {
        $provider = Auth::guard('provider')->user();
        return view('provider.dashboard.profile', compact('provider'));
    }

    /**
     * Update provider profile
     */
    public function updateProfile(Request $request)
    {
        $provider = Auth::guard('provider')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:providers,email,' . $provider->id,
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
        ]);

        $provider->name = $request->name;
        $provider->email = $request->email;
        $provider->city = $request->city;
        $provider->address = $request->address;
        $provider->bio = $request->bio;
        $provider->save();

        return redirect()->route('provider.profile')
            ->with('success', 'Profile updated successfully');
    }

    /**
     * Update provider avatar
     */
    public function updateAvatar(Request $request)
    {
        $provider = Auth::guard('provider')->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Delete old avatar if exists
        if ($provider->avatar) {
            Storage::disk('public')->delete($provider->avatar);
        }

        // Store new avatar
        $avatarPath = $request->file('avatar')->store('avatars/providers', 'public');
        $provider->avatar = $avatarPath;
        $provider->save();

        return redirect()->route('provider.profile')
            ->with('success', 'Profile picture updated successfully');
    }

    /**
     * Update provider documents
     */
    public function updateDocuments(Request $request)
    {
        $provider = Auth::guard('provider')->user();

        // Validate based on provider type
        if ($provider->type == 'personal') {
            $request->validate([
                'id_card' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            ]);

            // Delete old document if exists
            if ($provider->id_card) {
                Storage::disk('public')->delete($provider->id_card);
            }

            // Store new document
            $documentPath = $request->file('id_card')->store('documents/providers/id_cards', 'public');
            $provider->id_card = $documentPath;
        } else {
            $request->validate([
                'commercial_register' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            ]);

            // Delete old document if exists
            if ($provider->commercial_register) {
                Storage::disk('public')->delete($provider->commercial_register);
            }

            // Store new document
            $documentPath = $request->file('commercial_register')->store('documents/providers/commercial_registers', 'public');
            $provider->commercial_register = $documentPath;
        }

        $provider->save();

        return redirect()->route('provider.profile')
            ->with('success', 'Document uploaded successfully');
    }

    /**
     * Update provider password
     */
    public function updatePassword(Request $request)
    {
        $provider = Auth::guard('provider')->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Check current password
        if (!Hash::check($request->current_password, $provider->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'The current password is incorrect.'])
                ->withInput();
        }

        // Update password
        $provider->password = Hash::make($request->password);
        $provider->save();

        return redirect()->route('provider.profile')
            ->with('success', 'Password updated successfully');
    }
}
