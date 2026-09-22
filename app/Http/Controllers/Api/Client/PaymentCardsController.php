<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentCard;
use Illuminate\Support\Facades\Validator;

class PaymentCardsController extends Controller
{
    // ✅ Create a payment card
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_number' => 'required|string',
            'card_holder' => 'required|string',
            'expiration' => 'required|string',
            'cvv' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $paymentCard = PaymentCard::create([
            'client_id' => auth()->user()->id,  // Assuming client is authenticated
            'card_number' => $request->card_number,
            'card_holder' => $request->card_holder,
            'expiration' => $request->expiration,
            'cvv' => $request->cvv,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء بطاقة الدفع بنجاح',
            'paymentCard' => $paymentCard,
        ]);
    }

    // ✅ Get all cards for the authenticated client
    public function index()
    {
        $cards = PaymentCard::where('client_id', auth()->user()->id)->get();

        return response()->json([
            'status' => true,
            'data' => $cards,
        ]);
    }

    // ✅ Show a specific card
    public function show($id)
    {
        $card = PaymentCard::where('client_id', auth()->user()->id)->find($id);

        if (!$card) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم العثور على البطاقة',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $card,
        ]);
    }

    // ✅ Update a card
    public function update(Request $request, $id)
    {
        $card = PaymentCard::where('client_id', auth()->user()->id)->find($id);

        if (!$card) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم العثور على البطاقة',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'card_number' => 'sometimes|string',
            'card_holder' => 'sometimes|string',
            'expiration' => 'sometimes|string',
            'cvv' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $card->update($request->only(['card_number', 'card_holder', 'expiration', 'cvv']));

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث البطاقة بنجاح',
            'card' => $card,
        ]);
    }

    // ✅ Delete a card
    public function destroy($id)
    {
        $card = PaymentCard::where('client_id', auth()->user()->id)->find($id);

        if (!$card) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم العثور على البطاقة',
            ], 404);
        }

        $card->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف البطاقة بنجاح',
        ]);
    }
}
