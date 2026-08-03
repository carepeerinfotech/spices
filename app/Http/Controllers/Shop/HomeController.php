<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::active()->orderBy('sort_order')->take(4)->get();

        $featuredProducts = Product::active()->with('images')->latest()->take(4)->get();

        return view('shop.home', [
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
