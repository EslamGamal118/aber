<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Favorite;
use App\Models\Provider;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    public function addToFavorites(Request $request , $provider_id)
    {

        // التحقق ما إذا كانت العربة موجودة بالفعل في المفضلة
        $exists = Favorite::where('client_id', auth()->user()->id)
            ->where('provider_id', $provider_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => true,
                'message' => 'البائع موجود بالفعل في المفضلة',
            ]);
        }

        // إضافة العربة إلى المفضلة
        $favorite = Favorite::create([
            'client_id' => auth()->user()->id,
            'provider_id' => $provider_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تمت إضافة البائع إلى المفضلة بنجاح',
        ]);
    }

    /*****************************************************************************/
    public function removeFromFavorites($provider_id)
    {
        // إزالة العربة من المفضلة
        $deleted = Favorite::where('client_id', auth()->user()->id)
            ->where('provider_id', $provider_id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'status' => false,
                'message' => 'البايع غير موجود في المفضلة',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تمت إزالة البائع من المفضلة بنجاح',
        ]);
    }

        /*****************************************************************************/

public function getFavorites()
{
    $clientId = auth()->user()->id;

    // التحقق من وجود العميل
    $client = Client::find($clientId);
    if (!$client) {
        return response()->json([
            'status' => false,
            'message' => 'العميل غير موجود',
        ], 404);
    }

    // الحصول على البائعين المفضلين مع تقييماتهم
    $favorites = Favorite::where('client_id', $clientId)
        ->with(['provider' => function ($query) {
            $query->withAvg('ratings', 'rating');  // ratings is the relationship on Provider model
        }])
        ->get()
        ->map(function ($favorite) {
            return [
                'id' => $favorite->id,
                'provider' => $favorite->provider,
            ];
        });

    return response()->json([
        'status' => true,
        'message' => 'تم الحصول على قائمة المفضلة بنجاح',
        'favorites' => $favorites,
    ]);
}


    /******************************************************************************/
   public function checkFavorite($provider_id)
{
    // التحقق مما إذا كان البائع في المفضلة
    $isFavorite = Favorite::where('client_id', auth()->user()->id)
        ->where('provider_id', $provider_id)  // Changed from car_id to provider_id
        ->exists();

    return response()->json([
        'status' => true,
        'isFavorite' => $isFavorite,
    ]);
}

} 