<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
class AllProductsController extends Controller
{
    // get All Products
    public function index()
    {
        $products = Product::with('options','category' , 'vendor')->get();
        return response()->json(['status' => "success" , 'products' => $products]);
    } 
}
