<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index()
    {
        $cart = $this->cartService->getCart();

        return view('shop.cart.index', [
            'summary' => $this->cartService->summary($cart),
            'cartCount' => $cart->itemCount(),
        ]);
    }

    public function summary()
    {
        $cart = $this->cartService->getCart();

        return response()->json([
            'success' => true,
            'data' => $this->cartService->summary($cart),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $product = Product::active()->with('variants')->findOrFail($data['product_id']);
            $variant = ! empty($data['variant_id'])
                ? ProductVariant::where('product_id', $product->id)->findOrFail($data['variant_id'])
                : $product->defaultVariant();

            $cart = $this->cartService->add($product, $variant, $data['quantity'] ?? 1);

            return response()->json([
                'success' => true,
                'message' => $this->cartService->notice() ?? 'Added to cart.',
                'data' => $this->cartService->summary($cart),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(Request $request, int $item)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $cart = $this->cartService->update($item, $data['quantity']);

            return response()->json([
                'success' => true,
                'message' => $this->cartService->notice() ?? 'Cart updated.',
                'data' => $this->cartService->summary($cart),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $item)
    {
        try {
            $cart = $this->cartService->remove($item);

            return response()->json([
                'success' => true,
                'message' => 'Item removed.',
                'data' => $this->cartService->summary($cart),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
