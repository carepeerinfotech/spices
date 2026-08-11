<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request, CartService $cartService)
    {
        $category = null;
        if ($request->category) {
            $category = Category::active()->where('slug', $request->category)->first();
        }

        $products = Product::active()
            ->with(['category', 'images', 'variants', 'offers'])
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($request->q, fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(12);

        return view('shop.catalog', [
            'products' => $products,
            'categories' => Category::active()->orderBy('sort_order')->get(),
            'activeCategory' => $category,
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }

    public function show(string $slug, CartService $cartService)
    {
        $product = Product::active()
            ->where('slug', $slug)
            ->with(['category', 'images', 'offers', 'options.values', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();

        return view('shop.product.show', [
            'product' => $product,
            'defaultVariant' => $product->defaultVariant(),
            'related' => Product::active()
                ->with(['images', 'variants', 'offers'])
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->take(4)
                ->get(),
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }
}
