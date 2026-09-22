<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Offer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OffersController extends Controller
{
    // Display all offers
    public function index()
    {
        $offers = Offer::where('created_by_type', "admin")->latest()->paginate(10);
        return view('admin.offers.index', compact('offers'));
    }

    // Show the form to create a new offer
    public function create()
    {
        return view('admin.offers.create');
    }

    // Store a newly created offer


public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
    ]);

    // Handle image upload
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $filename = Str::slug($request->title) . '-' . time() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('offers', $filename, 'public');

        // Get full URL
        $fullUrl = Storage::disk('public')->url($path);
        $validated['image'] = $fullUrl;
    }

    $created_by_id = Auth::id();
    $validated['created_by_id'] = $created_by_id;

    Offer::create($validated);

    return redirect()->route('admin.offers.index')
                     ->with('success', 'Offer created successfully!');
}


    // Display a specific offer
    public function show(Offer $offer)
    {
        return view('admin.offers.show', compact('offer'));
    }

    // Show the form to edit an offer
    public function edit(Offer $offer)
    {
        return view('admin.offers.edit', compact('offer'));
    }

    // Update the specified offer
    public function update(Request $request, Offer $offer)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        // Handle image update
        if ($request->hasFile('image')) {
            // Delete old image
            if ($offer->image) {
                Storage::disk('public')->delete($offer->image);
            }
            
            $image = $request->file('image');
            $filename = Str::slug($request->title) . '-' . time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('offers', $filename, 'public');
            // Get full URL
            $fullUrl = Storage::disk('public')->url($path);
            $validated['image'] = $fullUrl;
        }

        if($request->has('status')) {
            $validated['status'] = $request->status;
        }
        $offer->update($validated);

        return redirect()->route('admin.offers.index')
                         ->with('success', 'Offer updated successfully!');
    }

    // Remove the specified offer
    public function destroy(Offer $offer)
    {
        // Delete associated image
        if ($offer->image) {
            Storage::disk('public')->delete($offer->image);
        }

        $offer->delete();

        return redirect()->route('admin.offers.index')
                         ->with('success', 'Offer deleted successfully!');
    }

    // API endpoint to get active offers (optional)
    public function activeOffers()
    {
        $offers = Offer::where('start_date', '<=', now())
                      ->where('end_date', '>=', now())
                      ->get();

        return response()->json($offers);
    }
}