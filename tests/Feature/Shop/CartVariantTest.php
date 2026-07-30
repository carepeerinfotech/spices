<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
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

    public function test_rejects_over_stock(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 1,
        ]);

        $this->postJson('/cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertStatus(422)->assertJsonPath('success', false);
    }
}
