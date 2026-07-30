<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\HomepageSlide;
use App\Models\Product;
use App\Services\CartService;

class HomeController extends Controller
{
    public function index(CartService $cartService)
    {
        $sections = HomepageSection::published()->orderBy('sort_order')->get()->keyBy('key');
        $slides = HomepageSlide::active()->orderBy('sort_order')->orderBy('id')->get();

        $featuredIds = data_get($sections->get('featured')?->content, 'product_ids', []);
        $categoryIds = data_get($sections->get('categories')?->content, 'category_ids', []);

        $featured = ! empty($featuredIds)
            ? Product::active()->with(['images', 'variants', 'category'])->whereIn('id', $featuredIds)->get()
            : Product::active()->featured()->with(['images', 'variants', 'category'])->latest()->take(8)->get();

        $categories = ! empty($categoryIds)
            ? Category::active()->whereIn('id', $categoryIds)->orderBy('sort_order')->get()
            : Category::active()->orderBy('sort_order')->take(7)->get();

        return view('shop.home', [
            'sections' => $sections,
            'slides' => $slides,
            'featured' => $featured,
            'categories' => $categories,
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }
}
