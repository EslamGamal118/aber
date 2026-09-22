<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\MenuPartition;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
class VendorMenuController extends Controller
{
public function index()
{
    $provider_id = auth()->user()->id;

    $menus = Menu::withCount('partitions')
        ->where('provider_id', $provider_id)
        ->get()
        ->map(function ($menu) {
            $menu->products_count = Product::where('menu_id', $menu->id)->count();
            return $menu;
        });

    return response()->json([
        'status' => 'success',
        'message' => 'Menus retrieved successfully',
        'data' => $menus
    ]);
}
    /*********************************************************************************/

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $provider_id = auth()->user()->id;

        $menu = Menu::create([
            'name' => $request->name,
            'description' => $request->description,
            'provider_id' => $provider_id 
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Menu created successfully',
            'menu' => $menu
        ], 201);
    }

    /*********************************************************************************/
    public function show($id)
    {
        $menu = Menu::where('provider_id', auth()->user()->id)->find($id);
        
        if (!$menu) {
            return response()->json([
                'message' => 'القائمة غير موجودة او لاتنتمي لك'
            ], 404);
        }
        
        return response()->json([
            'message' => 'Menu retrieved successfully',
            'menu' => $menu
        ]);
    }

    /*********************************************************************************/
    public function update(Request $request, string $id)
    {
        $menu = Menu::where('provider_id', auth()->user()->id)->find($id);
        
        if (!$menu) {
            return response()->json([
                'message' => 'Menu not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $menu->update([
            'name' => $request->name,
            'description' => $request->description
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Menu updated successfully',
            'menu' => $menu
        ]);
    }

    /**********************************************************************************/

    public function destroy(string $id)
    {
        $menu = Menu::where('provider_id', auth()->user()->id)->find($id);
        
        if (!$menu) {
            return response()->json([
                'message' => 'Menu not found'
            ], 404);
        }
        
        $menu->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Menu deleted successfully'
        ]);
    }

}
