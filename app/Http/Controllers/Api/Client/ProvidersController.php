<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Provider;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Rating;
use App\Models\Product;
use App\Models\Menu;
use App\Models\MenuPartition;
class ProvidersController extends Controller
{
public function index(Request $request)
{
    $query = Provider::query();

    if ($request->has('name')) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }

    if ($request->has('car_name')) {
        $query->where('car_name', 'like', '%' . $request->car_name . '%');
    }

    $providers = $query->get();

    return response()->json([
        'status' => true,
        'data' => $providers
    ]);
}

    
    /*********************************************************************************/
public function nearby(Request $request)
{
    $validator = Validator::make($request->all(), [
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'radius' => 'nullable|numeric|min:0',
        'category_id' => 'nullable|exists:products,category_id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'خطأ في البيانات المدخلة',
            'errors' => $validator->errors()
        ], 400);
    }

    $latitude = $request->input('latitude');
    $longitude = $request->input('longitude');
    $radius = $request->input('radius', 5);
    $categoryId = $request->input('category_id');

    $haversine = "(
        6371 * acos(
            cos(radians($latitude)) * cos(radians(providers.latitude)) * cos(radians(providers.longitude) - radians($longitude)) +
            sin(radians($latitude)) * sin(radians(providers.latitude))
        )
    )";

    $query = Provider::select([
            'providers.id',
            'providers.name',
            'providers.email',
            'providers.phone',
            'providers.latitude',
            'providers.longitude',
            'providers.avatar',
            'providers.provider_banner',
            DB::raw("$haversine AS distance"),
            DB::raw("AVG(ratings.rating) as avg_rating")
        ])
        ->leftJoin('products', 'products.vendor_id', '=', 'providers.id')
        ->leftJoin('ratings', 'ratings.product_id', '=', 'products.id')
        ->where('providers.status', 'active');

    if ($categoryId) {
        $query->where('products.category_id', $categoryId);
    }

    $providers = $query->groupBy(
            'providers.id',
            'providers.name',
            'providers.email',
            'providers.phone',
            'providers.latitude',
            'providers.longitude',
            'providers.avatar',
            'providers.provider_banner'
        )
        ->having('distance', '<=', $radius)
        ->orderBy('distance')
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'تم جلب المزودين القريبين بنجاح',
        'providers' => $providers
    ]);
}

   /*********************************************************************************/
public function highestRated(Request $request)
{
    $limit = $request->input('limit', 10);

    $products = Product::with('provider')
        ->join('ratings', 'ratings.product_id', '=', 'products.id')
        ->select(
            'products.id',
            'products.name',
            'products.description',
            'products.category_id',
            'products.partition_id',
            'products.menu_id',
            'products.image_url',
            'products.price',
            'products.discount_price',
            'products.is_available',
            'products.preparation_time',
            'products.created_at',
            'products.updated_at',
            'products.vendor_id',
            DB::raw('AVG(ratings.rating) as avg_rating')
        )
        ->groupBy(
            'products.id',
            'products.name',
            'products.description',
            'products.category_id',
            'products.partition_id',
            'products.menu_id',
            'products.image_url',
            'products.price',
            'products.discount_price',
            'products.is_available',
            'products.preparation_time',
            'products.created_at',
            'products.updated_at',
            'products.vendor_id'
        )
        ->orderByDesc('avg_rating')
        ->take($limit)
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'تم جلب المنتجات الأعلى تقييماً بنجاح',
        'products' => $products
    ]);
}

/**********************************************************************************/
    public function show($carId)
    {
        $car = Car::with([
                'menus.partitions' => function ($query) {
                    $query->with(['category:id,name,icon', 'elements']);
                }
            ])
            ->where('id', $carId)
            ->where('status', 'active')
            ->select([
                'id', 'name', 'description', 'phone', 'address', 
                'working_hours', 'imagePaths', 'latitude', 'longitude'
            ])
            ->withCount(['reviews as reviews_count'])
            ->withAvg(['reviews as rating'], 'rating')
            ->first();

        if (!$car) {
            return response()->json([
                'status' => false,
                'message' => 'عربة الطعام غير موجودة'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب تفاصيل عربة الطعام بنجاح',
            'car' => $car
        ]);
    }

    /**********************************************************************************/
    // show specific provider
public function showProvider($providerId)
{
    $provider = Provider::with('menus')->find($providerId);

    if (!$provider) {
        return response()->json([
            'status' => false,
            'message' => 'المزود غير موجود'
        ], 404);
    }

    // Calculate average rating
    $averageRating = $provider->ratings()->avg('rating');

    return response()->json([
        'status' => true,
        'message' => 'تم جلب تفاصيل المزود بنجاح',
        'provider' => $provider,
        'average_rating' => round($averageRating, 2) // round to 2 decimals
    ]);
}

/**********************************************************************************/
// show specific product
public function showProduct($productId)
{
    // Fetch the product with options
    $product = Product::with('productOptions')->find($productId);

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'المنتج غير موجود'
        ], 404);
    }

    // Group product options
    $groupedOptions = $this->groupProductOptions($product->productOptions);
    $productArray = $product->toArray();
    unset($productArray['product_options']);

    // 🔥 Fetch related products ordered together with this product
    $relatedProducts = Product::where('id', '!=', $productId)
        ->whereHas('orderItems.order.orderItems', function ($query) use ($productId) {
            $query->where('elementId', $productId);
        })
        ->withCount(['orderItems as ordered_count' => function ($query) use ($productId) {
            $query->whereHas('order.orderItems', function ($q) use ($productId) {
                $q->where('elementId', $productId);
            });
        }])
        ->orderByDesc('ordered_count')
        ->take(5)
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'تم جلب تفاصيل المنتج بنجاح',
        'product' => $productArray,
        'options' => $groupedOptions,
        'related_products' => $relatedProducts
    ]);
}
/**********************************************************************************/
// get menus for specific provider
public function providerMenus($providerId)
{
    $menus = Menu::where('provider_id', $providerId)->get();

    return response()->json([
        'status' => true,
        'message' => 'تم جلب المنيوات للمزود بنجاح',
        'data' => $menus
    ]);
}

/**********************************************************************************/
// get partitions for specific menu
public function menuPartitions($menuId){
    $partitions = MenuPartition::where('menu_id', $menuId)->get();

    return response()->json([
        'status' => true,
        'message' => 'تم جلب القسائم للمنيو بنجاح',
        'data' => $partitions
    ]);
}
/**********************************************************************************/
// get products for specific partition
public function partitionProducts($menu_id,$partition_id){
    $products = Product::where('menu_id', $menu_id)->where('partition_id', $partition_id)->get();

    return response()->json([
        'status' => true,
        'message' => 'تم جلب المنتجات للقسيمة بنجاح',
        'data' => $products
    ]);
}
/**********************************************************************************/
protected function groupProductOptions($productOptions)
{
    $grouped = [];

    foreach ($productOptions as $option) {
        $title = $option->title ?? 'بدون عنوان';

        if (!isset($grouped[$title])) {
            $grouped[$title] = [
                'title' => $title,
                'required' => $option->required ?? false,
                'values' => []
            ];
        }

        $grouped[$title]['values'][] = [
            'option' => $option->option ?? '',
            'price' => $option->price ?? '0',
        ];
    }

    return array_values($grouped);
}


} 