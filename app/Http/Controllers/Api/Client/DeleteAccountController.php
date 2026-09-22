<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Client;
use App\Models\DeletedUser;
use App\Models\Otp;
class DeleteAccountController extends Controller
{
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $client = Client::where('id', auth()->user()->id)->first();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Client not found',
            ], 404);
        }

        // Create audit record before deletion
        $deletedUser = DeletedUser::create([
            'client_id' => $client->id,
            'reason' => $request->reason ?? 'No reason provided',
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'deleted_at' => now(),
        ]);

        if (!$deletedUser) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete account',
            ], 500);
        }

        // Delete OTPs associated with the client phone number
        Otp::where('phone', $client->phone)->delete();

        // Revoke all Sanctum tokens (personal_access_tokens uses polymorphic morph, no FK cascade)
        $client->tokens()->delete();

        // Delete client (CASCADE handles orders, favorites, addresses, ratings, etc.)
        $client->delete();

        return response()->json([
            'status' => true,
            'message' => 'Account and all associated data have been permanently deleted',
        ]);
    }
}
