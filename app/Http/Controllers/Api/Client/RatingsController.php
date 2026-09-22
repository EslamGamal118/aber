<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rating;
class RatingsController extends Controller
{
public function store(Request $request)
{
    // Validate the request
    $validated = $request->validate([
        'provider_id' => 'required|exists:providers,id',
        'product_id'  => 'required|exists:products,id',
        'rating'      => 'required|integer|min:1|max:5',
        'review'      => 'nullable|string'
    ]);

    // Check if the client has already rated this product (optional, for uniqueness)
    $existingRating = Rating::where('client_id', auth()->user()->id)
                            ->where('product_id', $validated['product_id'])
                            ->first();

    if ($existingRating) {
        return response()->json([
            'message' => 'You have already rated this product.'
        ], 409);
    }

    // Store the rating
    $rating = Rating::create([
        'client_id'   => auth()->user()->id,
        'provider_id' => $validated['provider_id'],
        'product_id'  => $validated['product_id'],
        'rating'      => $validated['rating'],
        'review'      => $validated['review'] ?? null
    ]);

    return response()->json([
        'message' => 'Rating submitted successfully.',
        'data' => $rating
    ], 201);
}


    /*************************************************************************************/
}
