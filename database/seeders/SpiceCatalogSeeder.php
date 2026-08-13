<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\HomepageSlide;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Media\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class SpiceCatalogSeeder extends Seeder
{
    public function __construct(private readonly ImageService $images) {}

    public function run(): void
    {
        $categories = [
            [
                'name' => 'Turmeric',
                'slug' => 'turmeric',
                'description' => 'Golden turmeric powders for colour, aroma and wellness.',
                'image' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=400&h=400&fit=crop&q=80',
                'banner' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=1400&h=500&fit=crop&q=80',
                'sort_order' => 1,
            ],
            [
                'name' => 'Chilli',
                'slug' => 'chilli',
                'description' => 'Bold chilli powders with heat and vibrant red colour.',
                'image' => 'https://images.unsplash.com/photo-1588252303782-cb80119abd6d?w=400&h=400&fit=crop&q=80',
                'banner' => 'https://images.unsplash.com/photo-1588252303782-cb80119abd6d?w=1400&h=500&fit=crop&q=80',
                'sort_order' => 2,
            ],
            [
                'name' => 'Coriander',
                'slug' => 'coriander',
                'description' => 'Fresh ground coriander for everyday Indian cooking.',
                'image' => 'https://images.unsplash.com/photo-1505576399279-565b52d4ac71?w=400&h=400&fit=crop&q=80',
                'banner' => 'https://images.unsplash.com/photo-1505576399279-565b52d4ac71?w=1400&h=500&fit=crop&q=80',
                'sort_order' => 3,
            ],
            [
                'name' => 'Garam Masala',
                'slug' => 'garam-masala',
                'description' => 'Signature garam masala blends for rich, warm flavour.',
                'image' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400&h=400&fit=crop&q=80',
                'banner' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=1400&h=500&fit=crop&q=80',
                'sort_order' => 4,
            ],
            [
                'name' => 'Ground Spices',
                'slug' => 'ground-spices',
                'description' => 'Finely milled pure spices ready for your tadka.',
                'image' => 'https://images.unsplash.com/photo-1506368249639-73a05d4d8d80?w=400&h=400&fit=crop&q=80',
                'banner' => 'https://images.unsplash.com/photo-1506368249639-73a05d4d8d80?w=1400&h=500&fit=crop&q=80',
                'sort_order' => 5,
            ],
            [
                'name' => 'Blended Masalas',
                'slug' => 'blended-masalas',
                'description' => 'Kitchen-ready blends for sabzi, curry and snacks.',
                'image' => 'https://images.unsplash.com/photo-1606914501449-5a96b6afc1b3?w=400&h=400&fit=crop&q=80',
                'banner' => 'https://images.unsplash.com/photo-1606914501449-5a96b6afc1b3?w=1400&h=500&fit=crop&q=80',
                'sort_order' => 6,
            ],
            [
                'name' => 'Ginger Powder',
                'slug' => 'ginger-powder',
                'description' => 'Aromatic ginger powder for chai, sweets and curries.',
                'image' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?w=400&h=400&fit=crop&q=80',
                'banner' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?w=1400&h=500&fit=crop&q=80',
                'sort_order' => 7,
            ],
        ];

        $categoryModels = [];
        foreach ($categories as $data) {
            // Images live in the polymorphic images table, not on the model.
            $media = Arr::only($data, ['image', 'banner']);

            $category = Category::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge(Arr::except($data, ['image', 'banner']), ['is_active' => true])
            );

            foreach ($media as $collection => $url) {
                if ($url && $category->imagesIn($collection)->isEmpty()) {
                    $this->images->attach($category, $collection, $url);
                }
            }

            $categoryModels[$data['slug']] = $category;
        }

        // Hide legacy demo categories from the spice storefront
        Category::whereIn('slug', ['electronics', 'home-living', 'apparel'])->update(['is_active' => false]);
        Product::whereIn('sku', [
            'ELP-AP-005', 'ELP-AP-005-S', 'ELP-AP-005-M', 'ELP-AP-005-L',
            'ELP-HP-001', 'ELP-WT-002', 'ELP-HM-003', 'ELP-HM-004', 'ELP-AP-006',
        ])->update(['is_active' => false, 'is_featured' => false]);

        $products = [
            [
                'category' => 'turmeric',
                'name' => 'Turmeric Powder',
                'slug' => 'turmeric-powder',
                'sku' => 'ELP-SP-TUR-100',
                'short_description' => 'Authentic, Fresh and Pure',
                'description' => 'High-curcumin turmeric powder with rich colour and earthy aroma. Ideal for everyday cooking and golden milk.',
                'price' => 89,
                'compare_price' => 110,
                'stock' => 200,
                'image' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=800&h=800&fit=crop&q=80',
                'is_featured' => true,
                'weight' => 0.1,
            ],
            [
                'category' => 'chilli',
                'name' => 'Kashmiri Chilli Powder',
                'slug' => 'kashmiri-chilli-powder',
                'sku' => 'ELP-SP-CHI-100',
                'short_description' => 'Mild heat, vibrant colour',
                'description' => 'Premium Kashmiri chilli powder for restaurant-style red gravies without overpowering heat.',
                'price' => 99,
                'compare_price' => 129,
                'stock' => 180,
                'image' => 'https://images.unsplash.com/photo-1588252303782-cb80119abd6d?w=800&h=800&fit=crop&q=80',
                'is_featured' => true,
                'weight' => 0.1,
            ],
            [
                'category' => 'coriander',
                'name' => 'Coriander Powder',
                'slug' => 'coriander-powder',
                'sku' => 'ELP-SP-COR-100',
                'short_description' => 'Freshly ground aroma',
                'description' => 'Finely ground coriander seeds with citrusy notes — the base of countless Indian recipes.',
                'price' => 75,
                'compare_price' => 95,
                'stock' => 220,
                'image' => 'https://images.unsplash.com/photo-1505576399279-565b52d4ac71?w=800&h=800&fit=crop&q=80',
                'is_featured' => true,
                'weight' => 0.1,
            ],
            [
                'category' => 'garam-masala',
                'name' => 'Kitchen King Masala',
                'slug' => 'kitchen-king-masala',
                'sku' => 'ELP-SP-KK-100',
                'short_description' => 'All-purpose blend',
                'description' => 'A versatile blended masala that lifts sabzis, dals and snack recipes with balanced spice.',
                'price' => 120,
                'compare_price' => 149,
                'stock' => 160,
                'image' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=800&h=800&fit=crop&q=80',
                'is_featured' => true,
                'weight' => 0.1,
            ],
            [
                'category' => 'garam-masala',
                'name' => 'Garam Masala',
                'slug' => 'garam-masala-powder',
                'sku' => 'ELP-SP-GM-100',
                'short_description' => 'Warm finishing spice',
                'description' => 'Classic garam masala roasted and ground for a fragrant finish on curries and biryanis.',
                'price' => 135,
                'compare_price' => 165,
                'stock' => 150,
                'image' => 'https://images.unsplash.com/photo-1606914501449-5a96b6afc1b3?w=800&h=800&fit=crop&q=80',
                'is_featured' => true,
                'weight' => 0.1,
            ],
            [
                'category' => 'ground-spices',
                'name' => 'Cumin Powder',
                'slug' => 'cumin-powder',
                'sku' => 'ELP-SP-CUM-100',
                'short_description' => 'Earthy & aromatic',
                'description' => 'Pure cumin powder for tadka, raita and everyday tempering.',
                'price' => 95,
                'compare_price' => 115,
                'stock' => 190,
                'image' => 'https://images.unsplash.com/photo-1506368249639-73a05d4d8d80?w=800&h=800&fit=crop&q=80',
                'is_featured' => false,
                'weight' => 0.1,
            ],
            [
                'category' => 'blended-masalas',
                'name' => 'Pav Bhaji Masala',
                'slug' => 'pav-bhaji-masala',
                'sku' => 'ELP-SP-PB-100',
                'short_description' => 'Street-style flavour',
                'description' => 'Bold pav bhaji masala for authentic Mumbai-style bhaji at home.',
                'price' => 110,
                'compare_price' => 135,
                'stock' => 140,
                'image' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=800&h=800&fit=crop&q=80',
                'is_featured' => true,
                'weight' => 0.1,
            ],
            [
                'category' => 'ginger-powder',
                'name' => 'Ginger Powder',
                'slug' => 'dry-ginger-powder',
                'sku' => 'ELP-SP-GIN-100',
                'short_description' => 'Warm & spicy',
                'description' => 'Sun-dried ginger powder for chai, sweets, kadha and savoury recipes.',
                'price' => 105,
                'compare_price' => 130,
                'stock' => 170,
                'image' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?w=800&h=800&fit=crop&q=80',
                'is_featured' => true,
                'weight' => 0.1,
            ],
            [
                'category' => 'blended-masalas',
                'name' => 'Chaat Masala',
                'slug' => 'chaat-masala',
                'sku' => 'ELP-SP-CHT-100',
                'short_description' => 'Tangy snack sprinkle',
                'description' => 'Zesty chaat masala for fruits, raita, snacks and salads.',
                'price' => 85,
                'compare_price' => 99,
                'stock' => 210,
                'image' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=800&h=800&fit=crop&q=80',
                'is_featured' => false,
                'weight' => 0.1,
            ],
            [
                'category' => 'ground-spices',
                'name' => 'Black Pepper Powder',
                'slug' => 'black-pepper-powder',
                'sku' => 'ELP-SP-PEP-100',
                'short_description' => 'Sharp & pungent',
                'description' => 'Freshly milled black pepper for soups, eggs, salads and marinades.',
                'price' => 145,
                'compare_price' => 175,
                'stock' => 130,
                'image' => 'https://images.unsplash.com/photo-1532336414038-cf19250c5757?w=800&h=800&fit=crop&q=80',
                'is_featured' => false,
                'weight' => 0.1,
            ],
        ];

        $featuredIds = [];

        foreach ($products as $row) {
            $category = $categoryModels[$row['category']];
            $image = $row['image'] ?? null;
            unset($row['category'], $row['image']);

            $product = Product::updateOrCreate(
                ['sku' => $row['sku']],
                [
                    ...$row,
                    'category_id' => $category->id,
                    'is_active' => true,
                    'allow_cod' => true,
                    'allow_online' => true,
                    'has_variants' => false,
                    'tax_class' => 'gst_5',
                    'brand' => 'Elephant Spices',
                    'length' => 12,
                    'breadth' => 8,
                    'height' => 4,
                ]
            );

            ProductVariant::updateOrCreate(
                ['sku' => $product->sku],
                [
                    'product_id' => $product->id,
                    'name' => $product->name.' 100g',
                    'option_label' => '100g',
                    'option_values' => ['size' => '100g'],
                    'price' => $product->price,
                    'compare_price' => $product->compare_price,
                    'stock' => $product->stock,
                    'image' => $image,
                    'weight' => $product->weight,
                    'is_default' => true,
                    'is_active' => true,
                ]
            );

            if ($image && $product->imagesIn('gallery')->isEmpty()) {
                $this->images->attach($product, 'gallery', $image, ['alt' => $product->name]);
            }

            if ($product->is_featured) {
                $featuredIds[] = $product->id;
            }
        }

        $categoryIds = collect($categoryModels)->pluck('id')->values()->all();

        HomepageSection::updateOrCreate(['key' => 'hero'], [
            'type' => 'hero',
            'title' => 'Hero',
            'sort_order' => 1,
            'is_enabled' => true,
            'is_published' => true,
            'content' => [
                'brand' => 'Elephant Spices',
                'headline' => 'Leading Exporter & Supplier Of High Quality Spices.',
                'subline' => 'Authentic Indian spices sourced from renowned spice lands — fresh aroma, pure colour, uncompromised taste.',
                'cta_label' => 'Visit Our Shop',
                'cta_url' => '/shop',
                'image' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=1200&q=80',
            ],
        ]);

        HomepageSection::updateOrCreate(['key' => 'categories'], [
            'type' => 'categories',
            'title' => 'Shop by Category',
            'sort_order' => 2,
            'is_enabled' => true,
            'is_published' => true,
            'content' => [
                'subline' => 'Explore our pure and blended spices crafted for everyday cooking and festive feasts.',
                'category_ids' => $categoryIds,
            ],
        ]);

        HomepageSection::updateOrCreate(['key' => 'featured'], [
            'type' => 'featured',
            'title' => 'Best Selling Products',
            'sort_order' => 3,
            'is_enabled' => true,
            'is_published' => true,
            'content' => [
                'subline' => 'Customer favourites — authentic, fresh and pure.',
                'product_ids' => array_slice($featuredIds, 0, 8),
            ],
        ]);

        HomepageSection::updateOrCreate(['key' => 'why'], [
            'type' => 'why',
            'title' => 'Why Elephant Spices',
            'sort_order' => 4,
            'is_enabled' => true,
            'is_published' => true,
            'content' => [
                'subline' => 'Quality you can taste in every dish.',
                'items' => [
                    ['icon' => '🌿', 'title' => 'Pure Fresh'],
                    ['icon' => '✓', 'title' => '100% Pure'],
                    ['icon' => '🔬', 'title' => 'Lab Tested'],
                    ['icon' => '📦', 'title' => 'Hygiene Packed'],
                    ['icon' => '🍽', 'title' => 'Authentic Taste'],
                    ['icon' => '♡', 'title' => 'No Artificial Colors'],
                ],
            ],
        ]);

        HomepageSection::updateOrCreate(['key' => 'story'], [
            'type' => 'story',
            'title' => 'Brand Story',
            'sort_order' => 5,
            'is_enabled' => true,
            'is_published' => true,
            'content' => [
                'headline' => 'Three Generations of Flavour, Since 1974',
                'body' => 'At Elephant Spices, spice isn’t just an ingredient — it’s an emotion. From trusted classics to everyday kitchen essentials, we season life’s precious moments, one meal at a time.',
                'bullets' => [
                    'Sourced from India’s renowned spice lands',
                    'Consistent aroma, flavour and colour in every pack',
                    'Trusted in millions of households',
                ],
                'cta_label' => 'Read More',
                'cta_url' => '/pages/about-us',
                'image' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=1000&q=80',
            ],
        ]);

        HomepageSection::updateOrCreate(['key' => 'process'], [
            'type' => 'process',
            'title' => 'Manufacturing Process',
            'sort_order' => 6,
            'is_enabled' => true,
            'is_published' => true,
            'content' => [
                'subline' => 'From farm to pack — care at every step.',
                'steps' => [
                    ['title' => 'Pick'],
                    ['title' => 'Cleaning'],
                    ['title' => 'Grinding'],
                    ['title' => 'Roasting'],
                    ['title' => 'Packing'],
                    ['title' => 'Delivery'],
                ],
            ],
        ]);

        HomepageSection::updateOrCreate(['key' => 'collections'], [
            'type' => 'collections',
            'title' => 'Featured Collections',
            'sort_order' => 7,
            'is_enabled' => true,
            'is_published' => true,
            'content' => [
                'subline' => 'Powder, masala, whole spices and more.',
                'items' => [
                    [
                        'title' => 'Powder',
                        'url' => '/shop?category=ground-spices',
                        'image' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=700&h=900&fit=crop&q=80',
                    ],
                    [
                        'title' => 'Masala',
                        'url' => '/shop?category=blended-masalas',
                        'image' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=700&h=900&fit=crop&q=80',
                    ],
                    [
                        'title' => 'Whole',
                        'url' => '/shop?category=ground-spices',
                        'image' => 'https://images.unsplash.com/photo-1506368249639-73a05d4d8d80?w=700&h=900&fit=crop&q=80',
                    ],
                    [
                        'title' => 'Other',
                        'url' => '/shop',
                        'image' => 'https://images.unsplash.com/photo-1606914501449-5a96b6afc1b3?w=700&h=900&fit=crop&q=80',
                    ],
                ],
            ],
        ]);

        HomepageSection::updateOrCreate(['key' => 'testimonials'], [
            'type' => 'testimonials',
            'title' => 'What Our Customers Say',
            'sort_order' => 8,
            'is_enabled' => true,
            'is_published' => true,
            'content' => [
                'items' => [
                    [
                        'name' => 'Ananya Sharma',
                        'quote' => 'The turmeric colour is incredible and the aroma fills the kitchen. Elephant Spices is our household staple now.',
                        'badge' => 'Verified buyer',
                    ],
                    [
                        'name' => 'Rahul Mehta',
                        'quote' => 'Kitchen King Masala makes weekday sabzis taste restaurant-quality. Pure and consistent every pack.',
                        'badge' => 'Best seller fan',
                    ],
                    [
                        'name' => 'Priya Nair',
                        'quote' => 'Love the hygienic packaging and authentic garam masala. Shipping was quick across India.',
                        'badge' => 'Repeat customer',
                    ],
                ],
            ],
        ]);

        HomepageSection::updateOrCreate(['key' => 'newsletter'], [
            'type' => 'newsletter',
            'title' => 'Newsletter',
            'sort_order' => 9,
            'is_enabled' => true,
            'is_published' => true,
            'content' => [
                'headline' => 'Join Our Kitchen Family',
                'subline' => 'Subscribe for recipes, offers and new spice drops.',
            ],
        ]);

        HomepageSection::updateOrCreate(['key' => 'trust'], [
            'type' => 'trust',
            'title' => 'Trust',
            'sort_order' => 10,
            'is_enabled' => false,
            'is_published' => true,
            'content' => ['items' => []],
        ]);

        $slides = [
            [
                'title' => 'Premium spice bowls',
                'link_url' => '/shop',
                'image' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=1200&h=900&fit=crop&q=80',
                'mobile_image' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=800&h=900&fit=crop&q=80',
                'sort_order' => 1,
            ],
            [
                'title' => 'Turmeric harvest',
                'link_url' => '/shop?category=turmeric',
                'image' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=1200&h=900&fit=crop&q=80',
                'mobile_image' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=800&h=900&fit=crop&q=80',
                'sort_order' => 2,
            ],
            [
                'title' => 'Chilli market',
                'link_url' => '/shop?category=chilli',
                'image' => 'https://images.unsplash.com/photo-1588252303782-cb80119abd6d?w=1200&h=900&fit=crop&q=80',
                'mobile_image' => 'https://images.unsplash.com/photo-1588252303782-cb80119abd6d?w=800&h=900&fit=crop&q=80',
                'sort_order' => 3,
            ],
            [
                'title' => 'Whole spices',
                'link_url' => '/shop?category=ground-spices',
                'image' => 'https://images.unsplash.com/photo-1506368249639-73a05d4d8d80?w=1200&h=900&fit=crop&q=80',
                'mobile_image' => 'https://images.unsplash.com/photo-1506368249639-73a05d4d8d80?w=800&h=900&fit=crop&q=80',
                'sort_order' => 4,
            ],
        ];

        // Deactivate older non-spice demo slides
        HomepageSlide::query()->update(['is_active' => false]);

        foreach ($slides as $slide) {
            HomepageSlide::updateOrCreate(
                ['title' => $slide['title']],
                array_merge($slide, ['is_active' => true])
            );
        }
    }
}
