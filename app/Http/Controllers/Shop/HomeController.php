<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;

class HomeController extends Controller
{
    public function index(CartService $cartService)
    {
        $categories = Category::active()->orderBy('sort_order')->take(4)->get();

        $featuredProducts = Product::active()->with(['images', 'variants'])->latest()->take(4)->get();

        return view('shop.home', [
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }
}
