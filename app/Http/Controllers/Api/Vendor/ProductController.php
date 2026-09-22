<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Category;
use App\Models\Provider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'partition_id' => 'nullable|exists:menu_partitions,id',
            'menu_id' => 'nullable|exists:menus,id',
            'image_url' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_available' => 'boolean',
            'preparation_time' => 'nullable|integer|min:1',
            'discount_price' => 'nullable|numeric|min:0',
            'options' => 'nullable|array',
            'option.*.title' => 'required|string',
            'option.*.option' => 'required|string',
            'options.*.price' => 'nullable|string',
            'options.*.required' => 'boolean',
        ], [
            'name.required' => 'اسم المنتج مطلوب',
            'category_id.required' => 'تصنيف المنتج مطلوب',
            'category_id.exists' => 'تصنيف المنتج غير موجود',
            'price.required' => 'سعر المنتج مطلوب',
            'price.numeric' => 'سعر المنتج يجب أن يكون رقم',
            'price.min' => 'سعر المنتج يجب أن يكون موجب',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }
        
        // استخدم vendor_id من الطلب أو من المستخدم المصادق
        $vendorId = auth()->user()->id;
        
        // تحقق من وجود البائع
        $vendor = Provider::find($vendorId);
        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'البائع غير موجود'
            ], 404);
        }
        
        // تحقق من وجود التصنيف
        $category = Category::find($request->category_id);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'التصنيف غير موجود'
            ], 404);
        }
        
        // إنشاء المنتج
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'vendor_id' => $vendorId,
            'image_url' => $request->image_url,
            'price' => $request->price,
            'is_available' => $request->is_available ?? true,
            'preparation_time' => $request->preparation_time ?? null,
            'discount_price' => $request->discount_price ?? null,
            'partition_id' => $request->partition_id ?? null,
            'menu_id' => $request->menu_id ?? null,
        ]);
        
        if ($request->has('options') && is_array($request->options)) {
            foreach ($request->options as $group) {
                foreach ($group['values'] as $value) {
                    ProductOption::create([
                        'product_id' => $product->id,
                        'title' => $group['title'],
                        'option' => $value['option'],
                        'price' => $value['price'] ?? null,
                        'required' => $group['required'] ?? false,
                    ]);
                }
            }
}
        return response()->json([
            'status' => true,
            'message' => 'تم إضافة المنتج بنجاح',
            'product' => $product,
        ], 201);
    }
    
    /**************************************************************************************/
   public function destroy($productId)
{
    $vendorId = auth()->user()->id;

    // تحقق من وجود البائع
    if (!Provider::find($vendorId)) {
        return response()->json([
            'status' => false,
            'message' => 'البائع غير موجود'
        ], 404);
    }

    // جلب المنتج
    $product = Product::where('id', $productId)
        ->where('vendor_id', $vendorId)
        ->first();

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'المنتج غير موجود'
        ], 404);
    }

    // حذف خيارات المنتج أولاً
    ProductOption::where('product_id', $product->id)->delete();

    // حذف المنتج
    $product->delete();

    return response()->json([
        'status' => true,
        'message' => 'تم حذف المنتج وخياراته بنجاح'
    ]);
}

} 