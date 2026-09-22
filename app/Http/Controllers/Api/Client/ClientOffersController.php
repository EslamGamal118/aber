<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Offer;
class ClientOffersController extends Controller
{
    // get all Admin offers
    public function getAdminOffers()
    {
        $offers = Offer::where('created_by_type', 'admin')->where('status', 'active')->get();

        return response()->json([
            'status' => "success",
            'data' => $offers
        ]);
    }
    /***********************************************************************************/
    // get vendor offers
    public function getProviderOffers($provider_id)
    {
        $offers = Offer::where('provider_id', $provider_id)->where('status', 'active')->get();
        return response()->json([
            'status' => "success",
            'data' => $offers
        ]);
    }

    /************************************************************************************/
    // apply promo code
      public function applyPromoCode(Request $request)
{
    // Validate input
    $validator = Validator::make($request->all(), [
        'code' => 'required|string',
    ], [
        'code.required' => 'كود الخصم مطلوب',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first(),
        ], 422);
    }

    // Find offer by code
    $offer = Offer::where('code', $request->code)->first();

    if (!$offer) {
        return response()->json([
            'status' => false,
            'message' => 'كود الخصم غير صحيح',
        ], 422);
    }

    $now = now();

    // Check if code is active (start and end dates)
    if ($offer->start_date && $now->lt($offer->start_date)) {
        return response()->json([
            'status' => false,
            'message' => 'كود الخصم غير متاح بعد',
        ], 422);
    }

    if ($offer->end_date && $now->gt($offer->end_date)) {
        return response()->json([
            'status' => false,
            'message' => 'كود الخصم منتهي الصلاحية',
        ], 422);
    }

    // Check max uses
    if ($offer->max_uses !== null && $offer->uses >= $offer->max_uses) {
        return response()->json([
            'status' => false,
            'message' => 'كود الخصم تم استخدامه بالكامل',
        ], 422);
    }

    // Optionally check status if you use it to enable/disable promo
    if ($offer->status !== 'active') {
        return response()->json([
            'status' => false,
            'message' => 'كود الخصم غير مفعل',
        ], 422);
    }

    // Increase usage count
    $offer->increment('uses');

    // Return success with discount details
    return response()->json([
        'status' => true,
        'message' => 'تم تطبيق كود الخصم بنجاح',
        'discount_type' => $offer->discount_type,
        'discount_value' => $offer->discount_value,
    ]);
}
}
