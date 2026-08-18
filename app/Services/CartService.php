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
    private ?string $notice = null;

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

        return $cart->load(['items.product.images', 'items.product.offers', 'items.variant.images']);
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

            return $userCart->load(['items.product.images', 'items.product.offers', 'items.variant.images']);
        });
    }

    public function add(Product $product, ?ProductVariant $variant = null, int $quantity = 1): Cart
    {
        $this->notice = null;

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

        $stock = (int) $variant->stock;
        if ($stock < 1) {
            throw new \RuntimeException('This item is out of stock.');
        }

        $cart = $this->getCart();
        $item = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        // Asking for more than we have is not an error — the line is merged up to
        // whatever stock allows and the shopper is told what landed.
        $current = (int) ($item?->quantity ?? 0);
        $newQty = min($current + $quantity, $stock);

        if ($newQty === $current) {
            $this->notice = "Your cart already has all {$stock} we have in stock.";

            return $this->getCart();
        }

        if ($newQty < $current + $quantity) {
            $this->notice = "Only {$stock} in stock — cart updated to the maximum available.";
        }

        if ($item) {
            $item->update(['quantity' => $newQty, 'price' => $variant->price]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $newQty,
                'price' => $variant->price,
            ]);
        }

        return $this->getCart();
    }

    public function update(int $itemId, int $quantity): Cart
    {
        $this->notice = null;
        $cart = $this->getCart();
        $item = $cart->items()->where('id', $itemId)->firstOrFail();

        if ($quantity < 1) {
            $item->delete();

            return $this->getCart();
        }

        $stock = (int) ($item->variant?->stock ?? 0);
        if ($stock < 1) {
            throw new \RuntimeException('This item is out of stock.');
        }

        if ($quantity > $stock) {
            $quantity = $stock;
            $this->notice = "Only {$stock} in stock — quantity set to the maximum available.";
        }

        $item->update([
            'quantity' => $quantity,
            'price' => $item->variant?->price ?? $item->price,
        ]);

        return $this->getCart();
    }

    /**
     * Message describing any clamping the last add()/update() applied, so the
     * caller can report it as a success notice rather than an error.
     */
    public function notice(): ?string
    {
        return $this->notice;
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
        // Offers come off before tax and before the free-shipping threshold.
        $grossSubtotal = (float) $cart->items->sum(fn (CartItem $item) => $item->lineSubtotal());
        $discount = (float) $cart->items->sum(fn (CartItem $item) => $item->lineDiscount());
        $subtotal = round($grossSubtotal - $discount, 2);
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
            'gross_subtotal' => round($grossSubtotal, 2),
            'discount' => round($discount, 2),
            'subtotal' => round($subtotal, 2),
            'shipping' => round($shipping, 2),
            'tax' => $tax,
            'tax_percent' => $taxPercent,
            'total' => $total,
            'currency' => 'INR',
            'weight' => round($weight, 3),
            'items' => $cart->items->map(function (CartItem $item) {
                $offer = $item->offer();

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id,
                    'name' => $item->product?->name,
                    'variant_label' => $item->variant?->option_label,
                    'slug' => $item->product?->slug,
                    'image' => $item->variant?->imageUrl() ?: $item->product?->primaryImageUrl(),
                    'quantity' => $item->quantity,
                    'original_price' => (float) $item->price,
                    'price' => $item->discountedPrice(),
                    'discount' => $item->lineDiscount(),
                    'offer' => $offer ? [
                        'name' => $offer->name,
                        'label' => $offer->label(),
                        'discount_type' => $offer->discount_type,
                        'value' => (float) $offer->value,
                    ] : null,
                    'line_subtotal' => $item->lineSubtotal(),
                    'line_total' => $item->lineTotal(),
                    'stock' => $item->variant?->stock,
                ];
            }),
        ];
    }
}
