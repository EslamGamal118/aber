<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;

class ClientProfileController extends Controller
{
    /**
     * Get the authenticated client's profile.
     */
    public function getProfile()
    {
        $client = auth()->user();

        return response()->json([
            'status' => true,
            'data' => $client
        ]);
    }

    /************************************************************************************/
    public function update(Request $request)
    {
        $client = auth()->user();

        $validator = Validator::make($request->all(), [
            'name'     => 'nullable|string|max:255',
            'email'    => 'nullable|email|max:255|unique:clients,email,' . $client->id,
            'phone'    => 'nullable|string|unique:clients,phone,' . $client->id,
            'password' => 'nullable|string|min:6|confirmed',
        ], [
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        if ($request->filled('name')) {
            $client->name = $request->name;
        }

        if ($request->filled('email')) {
            $client->email = $request->email;
        }

        if ($request->filled('phone')) {
            $client->phone = $request->phone;
        }

        if ($request->filled('password')) {
            $client->password = Hash::make($request->password);
        }

        $client->save();

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data' => $client
        ]);
    }
}
