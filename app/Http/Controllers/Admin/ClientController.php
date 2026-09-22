<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display a listing of the clients.
     */
    public function index(Request $request)
    {
        $status = $request->status ?? 'all';
        
        $query = Client::query();
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->has('is_active')) {
            if($request->is_active === '1') {
                $query->where('is_active', 1);
            } elseif($request->is_active === '0') {
                $query->where('is_active', 0);
            }else {
                // return All Clients
                $query->where('is_active', 1)->orWhere('is_active', 0);
            }
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }
        
        $clients = $query->latest()->paginate(10);
        
        return view('admin.clients.index', compact('clients', 'status'));
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client)
    {
        return view('admin.clients.show', compact('client'));
    }

    /**
     * Block the specified client.
     */
    public function block(Request $request, Client $client)
    {
        if ($client->is_active === 0) {
            return back()->with('error', 'This client is already blocked.');
        }
        
        $client->is_active = 0;
        $client->save();
        
        // Here you could also send a notification to the client
        
        return back()->with('success', 'Client blocked successfully.');
    }

    /**
     * Unblock the specified client.
     */
    public function unblock(Client $client)
    {
        if ($client->is_active != 0) {
            return back()->with('error', 'This client is not blocked.');
        }
        
        $client->is_active = 1;
        $client->save();
        
        // Here you could also send a notification to the client
        
        return back()->with('success', 'Client unblocked successfully.');
    }
}