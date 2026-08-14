<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\Settings\SettingsService;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\Request;

class ShippingQuoteController extends Controller
{
    public function __construct(
        private ShippingManager $shipping,
        private SettingsService $settings,
        private CartService $cartService,
    ) {}

    public function product(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'pincode' => ['required', 'regex:/^\d{6}$/'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::active()->findOrFail($data['product_id']);
        $variant = ! empty($data['variant_id'])
            ? ProductVariant::where('product_id', $product->id)->findOrFail($data['variant_id'])
            : $product->defaultVariant();

        $weight = max(($variant?->shippingWeight() ?? 0.5) * ($data['qty'] ?? 1), 0.5);
        $pickup = (string) $this->settings->get('commerce', 'pickup_pincode', '110001');

        $result = $this->shipping->driver()->serviceability($pickup, $data['pincode'], $weight, 0);

        return response()->json($result + ['success' => $result['success'] ?? false]);
    }

    public function cart(Request $request)
    {
        $data = $request->validate([
            'pincode' => ['required', 'regex:/^\d{6}$/'],
            'cod' => ['sometimes', 'boolean'],
        ]);

        $cart = $this->cartService->getCart();
        $summary = $this->cartService->summary($cart);
        $pickup = (string) $this->settings->get('commerce', 'pickup_pincode', '110001');
        $codAmount = $request->boolean('cod') ? $summary['total'] : 0;

        $result = $this->shipping->driver()->serviceability(
            $pickup,
            $data['pincode'],
            max($summary['weight'], 0.5),
            $codAmount
        );

        if (! ($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        $rate = (float) data_get($result, 'cheapest.rate', 0);
        $summary = $this->cartService->summary($cart, $rate);

        return response()->json([
            'success' => true,
            'quote' => $result['cheapest'],
            'couriers' => $result['couriers'],
            'summary' => $summary,
        ]);
    }
}
