<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProviderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display a listing of the providers.
     */
    public function index(Request $request)
    {
        $status = $request->status ?? 'all';
        
        $query = Provider::query();
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }
        
        $providers = $query->latest()->paginate(10);
        
        return view('admin.providers.index', compact('providers', 'status'));
    }

    /**
     * Display the specified provider.
     */
    public function show(Provider $provider)
    {
        return view('admin.providers.show', compact('provider'));
    }

    /**
     * Approve the specified provider.
     */
    public function approve(Provider $provider)
    {
        if ($provider->status !== 'pending') {
            return back()->with('error', 'This provider is not pending approval.');
        }
        
        $provider->status = 'active';
        $provider->approved_at = now();
        $provider->save();
        
        // Here you could also send a notification to the provider
        
        return back()->with('success', 'Provider approved successfully.');
    }

    /**
     * Reject the specified provider.
     */
    public function reject(Request $request, Provider $provider)
    {
        if ($provider->status !== 'pending') {
            return back()->with('error', 'This provider is not pending approval.');
        }
        
        $request->validate([
            'status_message' => 'required|string|max:500',
        ]);
        
        $provider->status = 'rejected';
        $provider->status_message = $request->status_message;
        $provider->save();
        
        // Here you could also send a notification to the provider
        
        return back()->with('success', 'Provider rejected successfully.');
    }

    /**
     * Block the specified provider.
     */
    public function block(Request $request, Provider $provider)
    {
        if ($provider->status === 'blocked') {
            return back()->with('error', 'This provider is already blocked.');
        }
        
        $request->validate([
            'status_message' => 'required|string|max:500',
        ]);
        
        $provider->status = 'blocked';
        $provider->status_message = $request->status_message;
        $provider->save();
        
        // Here you could also send a notification to the provider
        
        return back()->with('success', 'Provider blocked successfully.');
    }

    /**
     * Unblock the specified provider.
     */
    public function unblock(Provider $provider)
    {
        if ($provider->status !== 'blocked') {
            return back()->with('error', 'This provider is not blocked.');
        }
        
        $provider->status = 'active';
        $provider->status_message = null;
        $provider->save();
        
        // Here you could also send a notification to the provider
        
        return back()->with('success', 'Provider unblocked successfully.');
    }

    /**
     * View provider documents.
     */
    public function viewDocument(Provider $provider, Request $request)
    {
        $documentType = $request->type;
        
        if ($documentType === 'id_card') {
            if (!$provider->id_card) {
                return back()->with('error', 'No ID card document available.');
            }
            
            return response()->file(Storage::disk('public')->path($provider->id_card));
        } elseif ($documentType === 'commercial_register') {
            if (!$provider->commercial_register) {
                return back()->with('error', 'No commercial register document available.');
            }
            
            return response()->file(Storage::disk('public')->path($provider->commercial_register));
        } else {
            return back()->with('error', 'Invalid document type.');
        }
    }
}
