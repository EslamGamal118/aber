<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Product;
use App\Models\Rating;
use Illuminate\Database\Seeder;

/**
 * Product ratings from seeded clients so GET /api/client/highestRatedProducts
 * and provider average ratings return meaningful data.
 */
class RatingSeeder extends Seeder
{
    /**
     * [product name => [[client index, rating, review], ...]]
     */
    public const RATINGS = [
        'كلاسيك بيف برجر' => [[0, 5, 'أفضل برجر جربته'], [1, 5, 'اللحم طري جداً'], [2, 4, 'ممتاز لكن السعر مرتفع قليلاً']],
        'ماشروم سويس برجر' => [[0, 5, null], [3, 5, 'الفطر رهيب'], [1, 4, null]],
        'سموكي BBQ برجر' => [[2, 4, 'الصوص لذيذ'], [3, 3, 'كان بارداً شوي']],
        'كرسبي تشيكن برجر' => [[1, 5, 'مقرمش ولذيذ'], [2, 5, null], [0, 4, null], [3, 5, 'أفضل من المطاعم الكبيرة']],
        'بطاطس مقلية' => [[0, 3, 'عادية']],
        'لاتيه' => [[1, 5, 'قهوة ممتازة'], [3, 5, 'أفضل لاتيه في الرياض'], [2, 4, null]],
        'كولد برو' => [[0, 5, null], [1, 4, 'قوي وحلو']],
        'سبانش لاتيه' => [[3, 5, 'حلو جداً'], [2, 5, null], [1, 5, 'بيحب الطلب كل يوم']],
        'قهوة سعودية' => [[0, 4, null], [2, 2, 'كانت باردة']],
        'كوكيز شوكولاتة' => [[1, 5, 'طري ولذيذ']],
    ];

    public function run(): void
    {
        $clients = Client::whereIn('email', array_column(ClientSeeder::CLIENTS, 'email'))->orderBy('id')->get()->values();
        $products = Product::all()->keyBy('name');

        foreach (self::RATINGS as $productName => $ratings) {
            $product = $products[$productName] ?? null;
            if (!$product) {
                continue;
            }

            foreach ($ratings as [$clientIndex, $stars, $review]) {
                $client = $clients[$clientIndex] ?? null;
                if (!$client) {
                    continue;
                }

                Rating::updateOrCreate(
                    ['client_id' => $client->id, 'product_id' => $product->id],
                    ['provider_id' => $product->vendor_id, 'rating' => $stars, 'review' => $review]
                );
            }
        }
    }
}
