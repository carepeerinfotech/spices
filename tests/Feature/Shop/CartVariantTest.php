<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartVariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(SettingsService::class)->setMany('shipping', [
            'charges_enabled' => true,
            'flat_rate' => 49,
            'free_above' => 999,
        ], [
            'charges_enabled' => ['type' => 'boolean'],
            'flat_rate' => ['type' => 'float'],
            'free_above' => ['type' => 'float'],
        ]);
        app(SettingsService::class)->set('commerce', 'gst_percent', 18, 'float');
    }

    public function test_can_add_variant_to_cart(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 500,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->postJson('/cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.items.0.variant_id', $variant->id);
    }

    public function test_clamps_over_stock_quantity_to_available_stock(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 2,
        ]);

        $this->postJson('/cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('message', 'Only 2 in stock — cart updated to the maximum available.');
    }

    public function test_merges_repeat_add_up_to_stock_without_failing(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 3,
            'is_active' => true,
        ]);

        // Driven through the service: each test HTTP request gets a fresh session,
        // which would hand every add() its own cart.
        $cart = app(CartService::class);

        $cart->add($product, $variant, 1);
        $this->assertSame(1, $cart->getCart()->itemCount());
        $this->assertNull($cart->notice());

        // Asking for the full stock on top of the existing line tops it up to 3.
        $cart->add($product, $variant, 3);
        $this->assertSame(3, $cart->getCart()->itemCount());
        $this->assertSame('Only 3 in stock — cart updated to the maximum available.', $cart->notice());

        // Nothing left to add — no exception, just an explanatory notice.
        $cart->add($product, $variant, 1);
        $this->assertSame(3, $cart->getCart()->itemCount());
        $this->assertSame('Your cart already has all 3 we have in stock.', $cart->notice());
    }

    public function test_update_clamps_quantity_to_stock(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 4,
            'is_active' => true,
        ]);

        $cart = app(CartService::class);
        $item = $cart->add($product, $variant, 1)->items->first();

        $cart->update($item->id, 99);

        $this->assertSame(4, $cart->getCart()->itemCount());
        $this->assertSame('Only 4 in stock — quantity set to the maximum available.', $cart->notice());
    }

    public function test_rejects_out_of_stock_variant(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 0,
        ]);

        $this->postJson('/cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertStatus(422)->assertJsonPath('success', false);
    }
}
