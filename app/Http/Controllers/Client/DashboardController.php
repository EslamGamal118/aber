<?php

namespace App\Http\Controllers\Client;

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
        // Apply client authentication middleware
        $this->middleware('auth:client');
    }

    /**
     * Show the client dashboard
     */
    public function index()
    {
        $client = Auth::guard('client')->user();
        return view('client.dashboard.index', compact('client'));
    }

    /**
     * Show client profile
     */
    public function showProfile()
    {
        $client = Auth::guard('client')->user();
        return view('client.dashboard.profile', compact('client'));
    }

    /**
     * Update client profile
     */
    public function updateProfile(Request $request)
    {
        $client = Auth::guard('client')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients,email,' . $client->id,
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'gender' => 'nullable|in:male,female',
            'birthdate' => 'nullable|date|before:today',
        ]);

        $client->name = $request->name;
        $client->email = $request->email;
        $client->city = $request->city;
        $client->address = $request->address;
        $client->bio = $request->bio;
        $client->gender = $request->gender;
        $client->birthdate = $request->birthdate;
        $client->save();

        return redirect()->route('client.profile')
            ->with('success', 'Profile updated successfully');
    }

    /**
     * Update client avatar
     */
    public function updateAvatar(Request $request)
    {
        $client = Auth::guard('client')->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Delete old avatar if exists
        if ($client->avatar) {
            Storage::disk('public')->delete($client->avatar);
        }

        // Store new avatar
        $avatarPath = $request->file('avatar')->store('avatars/clients', 'public');
        $client->avatar = $avatarPath;
        $client->save();

        return redirect()->route('client.profile')
            ->with('success', 'Profile picture updated successfully');
    }

    /**
     * Update client password
     */
    public function updatePassword(Request $request)
    {
        $client = Auth::guard('client')->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Check current password
        if (!Hash::check($request->current_password, $client->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'The current password is incorrect.'])
                ->withInput();
        }

        // Update password
        $client->password = Hash::make($request->password);
        $client->save();

        return redirect()->route('client.profile')
            ->with('success', 'Password updated successfully');
    }
}
