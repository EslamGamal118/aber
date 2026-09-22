<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuPartition;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Database\Seeder;

/**
 * Products for each seeded partition, linked to provider (vendor_id), menu,
 * partition and category, with add-on options for the customisable ones.
 */
class ProductSeeder extends Seeder
{
    /**
     * Products keyed by partition name. "options" => [title => [option => price]].
     */
    public const PRODUCTS = [
        'برجر لحم' => [
            ['name' => 'كلاسيك بيف برجر', 'category' => 'burger', 'price' => 32, 'preparation_time' => 12, 'description' => 'لحم واغيو 150 جم مع جبن شيدر وصوص خاص',
                'options' => ['الحجم' => ['عادي' => 0, 'دبل' => 14], 'إضافات' => ['جبن إضافي' => 4, 'بيكون لحم' => 6, 'بصل مكرمل' => 3]]],
            ['name' => 'ماشروم سويس برجر', 'category' => 'burger', 'price' => 36, 'discount_price' => 30, 'preparation_time' => 14, 'description' => 'فطر سوتيه وجبن سويسري',
                'options' => ['الحجم' => ['عادي' => 0, 'دبل' => 14]]],
            ['name' => 'سموكي BBQ برجر', 'category' => 'burger', 'price' => 38, 'preparation_time' => 15, 'description' => 'صوص باربكيو مدخن وحلقات بصل'],
        ],
        'برجر دجاج' => [
            ['name' => 'كرسبي تشيكن برجر', 'category' => 'burger', 'price' => 28, 'preparation_time' => 10, 'description' => 'صدر دجاج مقرمش مع مايونيز بالثوم',
                'options' => ['درجة الحرارة' => ['عادي' => 0, 'حار' => 0, 'حار جداً' => 0]]],
            ['name' => 'جريلد تشيكن برجر', 'category' => 'healthy', 'price' => 30, 'preparation_time' => 12, 'description' => 'صدر دجاج مشوي وخس وطماطم'],
        ],
        'جانبيات' => [
            ['name' => 'بطاطس مقلية', 'category' => 'burger', 'price' => 12, 'preparation_time' => 6, 'description' => 'بطاطس مقرمشة',
                'options' => ['الحجم' => ['صغير' => 0, 'كبير' => 5], 'الصوص' => ['كاتشب' => 0, 'جبن' => 4]]],
            ['name' => 'حلقات بصل', 'category' => 'burger', 'price' => 14, 'preparation_time' => 7, 'description' => 'حلقات بصل مقرمشة'],
        ],
        'مشروبات' => [
            ['name' => 'بيبسي', 'category' => 'coffee-drinks', 'price' => 5, 'preparation_time' => 1, 'description' => 'علبة 330 مل'],
            ['name' => 'عصير برتقال طازج', 'category' => 'coffee-drinks', 'price' => 14, 'preparation_time' => 4, 'description' => 'عصير طبيعي 100%'],
        ],
        'ساندويتشات' => [
            ['name' => 'ساندويتش بيض وجبن', 'category' => 'burger', 'price' => 18, 'preparation_time' => 8, 'description' => 'بيض مخفوق وجبن شيدر في خبز بريوش'],
            ['name' => 'ساندويتش حلومي', 'category' => 'healthy', 'price' => 20, 'preparation_time' => 8, 'description' => 'جبن حلومي مشوي وخضار'],
        ],
        'مشروبات ساخنة' => [
            ['name' => 'شاي كرك', 'category' => 'coffee-drinks', 'price' => 8, 'preparation_time' => 4, 'description' => 'شاي بالحليب والهيل'],
        ],
        'قهوة ساخنة' => [
            ['name' => 'إسبريسو', 'category' => 'coffee-drinks', 'price' => 12, 'preparation_time' => 3, 'description' => 'شوت مزدوج',
                'options' => ['الحجم' => ['سنجل' => 0, 'دبل' => 4]]],
            ['name' => 'لاتيه', 'category' => 'coffee-drinks', 'price' => 16, 'preparation_time' => 5, 'description' => 'إسبريسو مع حليب مبخر',
                'options' => ['الحجم' => ['صغير' => 0, 'وسط' => 3, 'كبير' => 6], 'الحليب' => ['عادي' => 0, 'شوفان' => 4, 'لوز' => 4]]],
            ['name' => 'قهوة سعودية', 'category' => 'coffee-drinks', 'price' => 10, 'preparation_time' => 4, 'description' => 'قهوة عربية بالهيل والزعفران'],
        ],
        'قهوة باردة' => [
            ['name' => 'آيس لاتيه', 'category' => 'coffee-drinks', 'price' => 18, 'preparation_time' => 5, 'description' => 'لاتيه بارد على الثلج',
                'options' => ['الحجم' => ['وسط' => 0, 'كبير' => 4]]],
            ['name' => 'كولد برو', 'category' => 'coffee-drinks', 'price' => 20, 'discount_price' => 17, 'preparation_time' => 3, 'description' => 'قهوة منقوعة 18 ساعة'],
            ['name' => 'سبانش لاتيه', 'category' => 'coffee-drinks', 'price' => 21, 'preparation_time' => 5, 'description' => 'لاتيه بالحليب المكثف'],
        ],
        'حلويات' => [
            ['name' => 'كوكيز شوكولاتة', 'category' => 'desserts', 'price' => 9, 'preparation_time' => 1, 'description' => 'كوكيز طري بقطع الشوكولاتة'],
            ['name' => 'تشيز كيك سان سبستيان', 'category' => 'desserts', 'price' => 24, 'preparation_time' => 2, 'description' => 'شريحة تشيز كيك محروق', 'is_available' => false],
        ],
        'موسمي' => [
            ['name' => 'بمبكن سبايس لاتيه', 'category' => 'coffee-drinks', 'price' => 22, 'preparation_time' => 5, 'description' => 'مشروب الخريف', 'is_available' => false],
        ],
    ];

    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');

        $partitions = MenuPartition::with('menu')->whereNotNull('menu_id')->get();

        foreach ($partitions as $partition) {
            foreach (self::PRODUCTS[$partition->name] ?? [] as $index => $data) {
                $options = $data['options'] ?? [];
                unset($data['options'], $data['category']);

                $product = Product::updateOrCreate(
                    ['partition_id' => $partition->id, 'name' => $data['name']],
                    $data + [
                        'menu_id' => $partition->menu_id,
                        'vendor_id' => $partition->menu->provider_id,
                        'category_id' => $categories[self::PRODUCTS[$partition->name][$index]['category']] ?? null,
                        'image_url' => 'https://placehold.co/600x400/png?text=' . rawurlencode($data['name']),
                        'is_available' => $data['is_available'] ?? true,
                        'discount_price' => $data['discount_price'] ?? null,
                    ]
                );

                $this->seedOptions($product, $options);
            }
        }
    }

    /**
     * @param array<string, array<string, int|float>> $options
     */
    protected function seedOptions(Product $product, array $options): void
    {
        foreach ($options as $title => $values) {
            $required = in_array($title, ['الحجم', 'درجة الحرارة'], true);

            foreach ($values as $option => $price) {
                ProductOption::updateOrCreate(
                    ['product_id' => $product->id, 'title' => $title, 'option' => $option],
                    ['price' => $price, 'required' => $required]
                );
            }
        }
    }
}
