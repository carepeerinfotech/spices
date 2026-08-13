<?php

namespace Tests\Feature\Shop;

use App\Models\Address;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $settings = app(SettingsService::class);
        $settings->setMany('payments', ['cod_enabled' => true, 'online_enabled' => true], [
            'cod_enabled' => ['type' => 'boolean'], 'online_enabled' => ['type' => 'boolean'],
        ]);
        $settings->setMany('shipping', ['charges_enabled' => true, 'flat_rate' => 49, 'free_above' => 9999], [
            'charges_enabled' => ['type' => 'boolean'], 'flat_rate' => ['type' => 'float'], 'free_above' => ['type' => 'float'],
        ]);
        $settings->setMany('shiprocket', ['enabled' => true, 'driver' => 'fake'], [
            'enabled' => ['type' => 'boolean'],
        ]);
        $settings->set('commerce', 'pickup_pincode', '110001');
        $settings->set('commerce', 'gst_percent', 18, 'float');
        $settings->set('notifications', 'enabled', false, 'boolean');
    }

    public function test_running_offer_discounts_the_cart_summary(): void
    {
        [$user, , $product, $variant] = $this->scenario();
        $this->offerOn($product, 'percentage', 10);

        $this->actingAs($user)->postJson('/cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk()
            ->assertJsonPath('data.gross_subtotal', 2000)
            ->assertJsonPath('data.discount', 200)
            ->assertJsonPath('data.subtotal', 1800)
            // GST is charged on the discounted subtotal, not the list price.
            ->assertJsonPath('data.tax', 324)
            ->assertJsonPath('data.items.0.original_price', 1000)
            ->assertJsonPath('data.items.0.price', 900)
            ->assertJsonPath('data.items.0.offer.label', '10% off');
    }

    public function test_order_is_charged_at_the_offer_price_and_records_the_offer(): void
    {
        [$user, $address, $product, $variant] = $this->scenario();
        $offer = $this->offerOn($product, 'percentage', 10, 'Monsoon Sale');

        $this->actingAs($user)->postJson('/cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk();

        $this->actingAs($user)->postJson('/checkout', [
            'shipping_address_id' => $address->id,
            'billing_same_as_shipping' => 1,
            'billing_name' => $address->name,
            'billing_email' => $user->email,
            'billing_phone' => $address->phone,
            'billing_address_line1' => $address->address_line1,
            'billing_city' => $address->city,
            'billing_state' => $address->state,
            'billing_postal_code' => $address->postal_code,
            'billing_country' => $address->country,
            'payment_method' => 'cod',
            'shipping_rate' => 49,
            'courier_name' => 'Fake Express',
            'estimated_delivery_days' => 3,
        ])->assertOk()->assertJsonPath('success', true);

        $order = \App\Models\Order::latest('id')->firstOrFail();

        // Charged at the discounted price: 2 x 900, +49 shipping, +18% GST on 1800.
        $this->assertEquals(900.00, (float) $order->items->first()->price);
        $this->assertEquals(1800.00, (float) $order->items->first()->total);
        $this->assertEquals(1800.00, (float) $order->subtotal);
        $this->assertEquals(324.00, (float) $order->tax_amount);
        $this->assertEquals(2173.00, (float) $order->total);

        $this->assertDatabaseHas('order_offers', [
            'order_id' => $order->id,
            'product_offer_id' => $offer->id,
            'name' => 'Monsoon Sale',
            'discount_type' => 'percentage',
            'unit_discount' => 100.00,
            'discount_amount' => 200.00,
        ]);
        $this->assertEquals(200.00, $order->offersDiscount());
    }

    public function test_expired_offer_is_ignored(): void
    {
        [$user, , $product, $variant] = $this->scenario();
        ProductOffer::create([
            'product_id' => $product->id,
            'discount_type' => 'flat',
            'value' => 250,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($user)->postJson('/cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk()
            ->assertJsonPath('data.discount', 0)
            ->assertJsonPath('data.subtotal', 1000)
            ->assertJsonPath('data.items.0.offer', null);
    }

    public function test_flat_offer_never_pushes_a_line_below_zero(): void
    {
        [$user, , $product, $variant] = $this->scenario();
        $this->offerOn($product, 'flat', 5000);

        $this->actingAs($user)->postJson('/cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk()
            ->assertJsonPath('data.discount', 1000)
            ->assertJsonPath('data.subtotal', 0)
            ->assertJsonPath('data.items.0.price', 0);
    }

    public function test_offer_window_typed_in_store_time_is_stored_as_utc(): void
    {
        config(['app.display_timezone' => 'Asia/Kolkata']);
        $product = Product::factory()->create(['price' => 1000]);

        app(\App\Services\Catalog\ProductCatalogService::class)->save(
            $product,
            $product->only(['category_id', 'name', 'slug', 'sku', 'price', 'stock']),
            [], [],
            [['discount_type' => 'flat', 'value' => 50, 'starts_at' => '2026-08-11T15:50', 'ends_at' => '2026-08-16T15:50']],
            null
        );

        $offer = $product->fresh()->offers->firstOrFail();

        // 15:50 IST is 10:20 UTC, and reads back as 15:50 for the admin.
        $this->assertSame('2026-08-11 10:20:00', $offer->starts_at->toDateTimeString());
        $this->assertSame('2026-08-11T15:50', \App\Support\LocalTime::forInput($offer->starts_at));
        $this->assertSame('16 Aug 2026, 3:50 PM', \App\Support\LocalTime::display($offer->ends_at));
    }

    private function offerOn(Product $product, string $type, float $value, ?string $name = null): ProductOffer
    {
        return ProductOffer::create([
            'product_id' => $product->id,
            'name' => $name,
            'discount_type' => $type,
            'value' => $value,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);
    }

    private function scenario(): array
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $address = Address::create([
            'user_id' => $user->id,
            'label' => 'Home',
            'name' => $user->name,
            'phone' => '9999999999',
            'address_line1' => 'Test street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110001',
            'country' => 'IN',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);
        $product = Product::factory()->create(['allow_cod' => true, 'price' => 1000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 1000,
            'stock' => 5,
        ]);

        return [$user, $address, $product, $variant];
    }
}
