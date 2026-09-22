<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;
class CitiesController extends Controller
{
    // Get all cities
    public function index()
    {
        $cities = City::all();
        return response()->json([
            'message' => 'تم الحصول على المدن بنجاح',
            'data' => $cities
        ]);
    }
}
