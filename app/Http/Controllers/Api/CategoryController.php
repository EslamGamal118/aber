<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * عرض قائمة التصنيفات النشطة
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $categories = Category::where('active', true)->get();
        
        return response()->json([
            'status' => true,
            'message' => 'تم جلب التصنيفات بنجاح',
            'categories' => $categories
        ]);
    }

    /**
     * عرض جميع التصنيفات (للمسؤولين)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $categories = Category::all();
        
        return response()->json([
            'status' => true,
            'message' => 'تم جلب جميع التصنيفات بنجاح',
            'categories' => $categories
        ]);
    }

    /**
     * إنشاء تصنيف جديد
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:categories',
            'icon' => 'nullable|string',
            'active' => 'boolean'
        ], [
            'name.required' => 'اسم التصنيف مطلوب',
            'name.unique' => 'اسم التصنيف موجود بالفعل',
            'name.max' => 'اسم التصنيف يجب ألا يتجاوز 100 حرف'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        
        $category = Category::create($request->all());
        
        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء التصنيف بنجاح',
            'category' => $category
        ], 201);
    }

    /**
     * عرض تصنيف محدد
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'التصنيف غير موجود'
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'message' => 'تم جلب التصنيف بنجاح',
            'category' => $category
        ]);
    }

    /**
     * تحديث تصنيف
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'التصنيف غير موجود'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:100|unique:categories,name,' . $id,
            'icon' => 'nullable|string',
            'active' => 'boolean'
        ], [
            'name.unique' => 'اسم التصنيف موجود بالفعل',
            'name.max' => 'اسم التصنيف يجب ألا يتجاوز 100 حرف'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        
        $category->update($request->all());
        
        return response()->json([
            'status' => true,
            'message' => 'تم تحديث التصنيف بنجاح',
            'category' => $category
        ]);
    }

    /**
     * حذف تصنيف
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'التصنيف غير موجود'
            ], 404);
        }
        
        // تحقق ما إذا كان هناك أقسام مرتبطة بهذا التصنيف
        if ($category->partitions()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن حذف التصنيف لأنه مستخدم في بعض الأقسام'
            ], 422);
        }
        
        $category->delete();
        
        return response()->json([
            'status' => true,
            'message' => 'تم حذف التصنيف بنجاح'
        ]);
    }
}
