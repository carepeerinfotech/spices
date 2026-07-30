<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function __construct(private SettingsService $settings) {}

    public function getCart(): Cart
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        $cart = Cart::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId)->whereNull('user_id'))
            ->first();

        if (! $cart) {
            $cart = Cart::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
            ]);
        }

        return $cart->load(['items.product.images', 'items.variant']);
    }

    public function mergeSessionCartIntoUser(int $userId, ?string $guestSessionId = null): Cart
    {
        $sessionId = Session::getId();
        $guestSessionId ??= $sessionId;

        return DB::transaction(function () use ($userId, $sessionId, $guestSessionId) {
            $guestCart = Cart::query()
                ->whereNull('user_id')
                ->where(function ($q) use ($guestSessionId, $sessionId) {
                    $q->where('session_id', $guestSessionId)
                        ->orWhere('session_id', $sessionId);
                })
                ->with('items')
                ->first();

            $userCart = Cart::query()->firstOrCreate(
                ['user_id' => $userId],
                ['session_id' => $sessionId]
            );

            if ($guestCart && $guestCart->id !== $userCart->id) {
                foreach ($guestCart->items as $item) {
                    $existing = $userCart->items()
                        ->where('product_id', $item->product_id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->first();

                    if ($existing) {
                        $variant = ProductVariant::find($item->product_variant_id);
                        $newQty = min(($existing->quantity + $item->quantity), $variant?->stock ?? $existing->quantity);
                        $existing->update([
                            'quantity' => max(1, $newQty),
                            'price' => $variant?->price ?? $existing->price,
                        ]);
                    } else {
                        $userCart->items()->create([
                            'product_id' => $item->product_id,
                            'product_variant_id' => $item->product_variant_id,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                        ]);
                    }
                }

                $guestCart->items()->delete();
                $guestCart->delete();
            }

            $userCart->update(['session_id' => $sessionId]);

            return $userCart->load(['items.product.images', 'items.variant']);
        });
    }

    public function add(Product $product, ?ProductVariant $variant = null, int $quantity = 1): Cart
    {
        if (! $product->is_active) {
            throw new \RuntimeException('Product is not available.');
        }

        $variant ??= $product->defaultVariant();
        if (! $variant || ! $variant->is_active) {
            throw new \RuntimeException('Selected variation is not available.');
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }

        if ($variant->stock < $quantity) {
            throw new \RuntimeException('Insufficient stock available.');
        }

        $cart = $this->getCart();
        $item = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($item) {
            $newQty = $item->quantity + $quantity;
            if ($variant->stock < $newQty) {
                throw new \RuntimeException('Insufficient stock available.');
            }
            $item->update(['quantity' => $newQty, 'price' => $variant->price]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'price' => $variant->price,
            ]);
        }

        return $this->getCart();
    }

    public function update(int $itemId, int $quantity): Cart
    {
        $cart = $this->getCart();
        $item = $cart->items()->where('id', $itemId)->firstOrFail();

        if ($quantity < 1) {
            $item->delete();

            return $this->getCart();
        }

        $stock = $item->variant?->stock ?? 0;
        if ($stock < $quantity) {
            throw new \RuntimeException('Insufficient stock available.');
        }

        $item->update([
            'quantity' => $quantity,
            'price' => $item->variant?->price ?? $item->price,
        ]);

        return $this->getCart();
    }

    public function remove(int $itemId): Cart
    {
        $cart = $this->getCart();
        $cart->items()->where('id', $itemId)->delete();

        return $this->getCart();
    }

    public function clear(): void
    {
        $cart = $this->getCart();
        $cart->items()->delete();
    }

    public function summary(Cart $cart, ?float $shippingOverride = null): array
    {
        $subtotal = (float) $cart->items->sum(fn (CartItem $item) => $item->price * $item->quantity);
        $taxPercent = $this->settings->float('commerce', 'gst_percent', 18);
        $shippingEnabled = $this->settings->bool('shipping', 'charges_enabled', true);
        $freeAbove = $this->settings->float('shipping', 'free_above', 999);
        $fallbackShipping = $this->settings->float('shipping', 'flat_rate', 49);

        if ($shippingOverride !== null) {
            $shipping = $shippingEnabled ? $shippingOverride : 0;
        } else {
            $shipping = 0;
            if ($shippingEnabled && $subtotal > 0) {
                $shipping = $subtotal >= $freeAbove ? 0 : $fallbackShipping;
            }
        }

        $tax = round($subtotal * ($taxPercent / 100), 2);
        $total = round($subtotal + $shipping + $tax, 2);
        $weight = (float) $cart->items->sum(fn (CartItem $item) => ($item->variant?->shippingWeight() ?? 0.5) * $item->quantity);

        return [
            'item_count' => (int) $cart->items->sum('quantity'),
            'subtotal' => round($subtotal, 2),
            'shipping' => round($shipping, 2),
            'tax' => $tax,
            'tax_percent' => $taxPercent,
            'total' => $total,
            'currency' => 'INR',
            'weight' => round($weight, 3),
            'allow_cod' => $cart->items->every(fn (CartItem $i) => (bool) ($i->product?->allow_cod ?? true)),
            'allow_online' => $cart->items->every(fn (CartItem $i) => (bool) ($i->product?->allow_online ?? true)),
            'items' => $cart->items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'name' => $item->product?->name,
                'variant_label' => $item->variant?->option_label,
                'slug' => $item->product?->slug,
                'image' => $item->variant?->imageUrl() ?: $item->product?->primaryImageUrl(),
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'line_total' => $item->lineTotal(),
                'stock' => $item->variant?->stock,
            ]),
        ];
    }
}
