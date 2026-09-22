<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Offer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
class OffersController extends Controller
{
    // List all offers by the authenticated provider
    public function index()
    {
        $offers = Offer::where('provider_id', Auth::id())->get();

        return response()->json([
            'status' => "success",
            'data' => $offers
        ]);
    }

    /**********************************************************************************/
    // Store a new offer
    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title'           => 'required|string|max:255',
        'code'            => 'nullable|string|max:255|unique:offers',
        'discount_type'   => 'required|in:fixed,percentage',
        'discount_value'  => 'required|numeric|min:0',
        'max_uses'        => 'nullable|integer|min:0',
        'start_date'      => 'required|date',
        'end_date'        => 'required|date|after_or_equal:start_date',
        'image'           => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    }

    $offer = Offer::create([
        'title'                  => $request->title,
        'code'                   => $request->code,
        'discount_type'          => $request->discount_type,
        'discount_value'         => $request->discount_value,
        'start_date'             => $request->start_date,
        'end_date'               => $request->end_date,
        'max_uses'               => $request->max_uses,
        'image'                  => $request->image,
        'provider_id'            => Auth::id(),
        'created_by_id'          => Auth::id(),
        'created_by_type'        => 'provider',
    ]);

    return response()->json([
        'status' => true,
        'message' => 'تم إنشاء العرض بنجاح',
        'data' => $offer
    ]);
}


    /***********************************************************************************/
    // Update an existing offer
    public function update(Request $request, $id)
    {
        $offer = Offer::where('id', $id)->where('provider_id', Auth::id())->first();

        if (!$offer) {
            return response()->json(['status' => false, 'message' => 'العرض غير موجود'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'           => 'sometimes|string|max:255',
            'description'     => 'nullable|string',
            'discount_type'   => 'sometimes|in:fixed,percentage',
            'discount_value'  => 'sometimes|numeric|min:0',
            'start_date'      => 'sometimes|date',
            'end_date'        => 'sometimes|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $offer->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث العرض بنجاح',
            'data' => $offer
        ]);
    }

    // Delete an offer
    public function destroy($id)
    {
        $offer = Offer::where('id', $id)->where('provider_id', Auth::id())->first();

        if (!$offer) {
            return response()->json(['status' => false, 'message' => 'العرض غير موجود'], 404);
        }

        $offer->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف العرض بنجاح',
        ]);
    }
}
