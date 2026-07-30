<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedCheckoutTest extends TestCase
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

    public function test_guest_cannot_checkout(): void
    {
        $this->get('/checkout')->assertRedirect('/login');
    }

    public function test_unverified_user_cannot_checkout(): void
    {
        $user = User::factory()->unverified()->create(['is_active' => true]);
        $this->actingAs($user)->get('/checkout')->assertRedirect('/email/verify');
    }

    public function test_verified_user_can_place_cod_order(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
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

        $product = Product::factory()->create(['allow_cod' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 1000,
            'stock' => 5,
        ]);

        $this->actingAs($user)->postJson('/cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $response = $this->actingAs($user)->postJson('/checkout', [
            'shipping_address_id' => $address->id,
            'billing_same_as_shipping' => 1,
            'payment_method' => 'cod',
            'shipping_rate' => 49,
            'courier_name' => 'Fake Express',
            'estimated_delivery_days' => 3,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method' => 'cod',
            'customer_email' => $user->email,
        ]);
        $this->assertEquals(4, $variant->fresh()->stock);
    }
}
