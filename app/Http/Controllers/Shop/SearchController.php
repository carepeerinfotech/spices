<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggest(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        if ($term === '') {
            return response()->json(['products' => []]);
        }

        $products = Product::active()
            ->with(['images'])
            ->where('name', 'like', '%'.$term.'%')
            ->orderBy('name')
            ->take(6)
            ->get()
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'url' => route('shop.product', $product->slug),
                'image' => $product->primaryImageUrl(),
                'price' => $product->formattedPrice(),
            ]);

        return response()->json(['products' => $products]);
    }
}
