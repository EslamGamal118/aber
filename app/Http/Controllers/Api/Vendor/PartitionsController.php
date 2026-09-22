<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\MenuPartition;
use App\Models\Element;
use App\Models\Car;
use Illuminate\Support\Facades\Validator;
class PartitionsController extends Controller
{
     public function getPartitions(string $menuId)
    {
        // Check if menu exists
        $menu = Menu::find($menuId);
        if (!$menu) {
            return response()->json([
                'message' => 'القائمة غير موجودة'
            ], 404);
        }

        $partitions = MenuPartition::with('products')->where('menu_id', $menuId)->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'تم الحصول على أقسام القائمة بنجاح',
            'data' => $partitions
        ]);
    }

/******************************************************************************/
     public function createPartition(Request $request , $menu_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'اسم القسم مطلوب',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // check if menu exists or not
        $menu = Menu::find($menu_id);
        if (!$menu) {
            return response()->json([
                'message' => 'Menu not found'
            ], 404);
        }

        // check if menu provided is owns to this vendor or not
        if ($menu->provider_id != auth()->user()->id) {
            return response()->json([
                'message' => 'هذه القائمة لا تنتمي لك'
            ], 404);
        }

        $partition = MenuPartition::create([
            'name' => $request->name,
            'description' => $request->description ?? null,
            'menu_id' => $menu_id,
            'status' => $request->status ?? 'active' ,
        ]);

        return response()->json([
            'message' => 'Menu partition created successfully',
            'partition' => $partition
        ], 201);
    }

    /*******************************************************************************/
    public function updatePartition(Request $request, string $menuId, string $partitionId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'اسم القسم مطلوب',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if menu exists
        $menu = Menu::find($menuId);
        if (!$menu) {
            return response()->json([
                'message' => 'القائمة غير موجودة'
            ], 404);
        }

        // Check if partition exists and belongs to the menu
        $partition = MenuPartition::where('id', $partitionId)
            ->where('menu_id', $menuId)
            ->first();
            
        if (!$partition) {
            return response()->json([
                'message' => 'قسم القائمة غير موجود أو لا ينتمي إلى هذه القائمة'
            ], 404);
        }

        $partition->name = $request->name ?? $partition->name;
        $partition->description = $request->description ?? $partition->description;
        $partition->status = $request->status ?? $partition->status;
        $partition->save();
        
        return response()->json([
            'message' => 'تم تحديث قسم القائمة بنجاح',
            'partition' => $partition
        ]);
    }

    /*****************************************************************************/
    public function deletePartition(string $menuId, string $partitionId)
    {
        // Check if menu exists
        $menu = Menu::find($menuId);
        if (!$menu) {
            return response()->json([
                'message' => 'القائمة غير موجودة'
            ], 404);
        }

        // Check if partition exists and belongs to the menu
        $partition = MenuPartition::where('id', $partitionId)
            ->where('menu_id', $menuId)
            ->first();
            
        if (!$partition) {
            return response()->json([
                'message' => 'قسم القائمة غير موجود أو لا ينتمي إلى هذه القائمة'
            ], 404);
        }

        // Delete the partition
        $partition->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف قسم القائمة بنجاح'
        ]);
    }
}
