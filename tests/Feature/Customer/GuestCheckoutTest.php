<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Checkout creates the customer account from the form itself, so nobody is sent
 * to /login on the way to placing an order.
 */
class GuestCheckoutTest extends TestCase
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

    /**
     * The test client has no cookie jar, so every request would otherwise start
     * a fresh session — and a guest cart hangs off the session id.
     */
    private function keepSession(): void
    {
        $this->withCookie(config('session.cookie'), app('session')->getId());
    }

    /**
     * `postJson()` cannot be used here: it sends only unencrypted cookies, which
     * drops the session set up by keepSession(). A form post asking for JSON
     * back is what the storefront sends anyway.
     */
    private function postForm(string $uri, array $data): \Illuminate\Testing\TestResponse
    {
        return $this->post($uri, $data, ['Accept' => 'application/json']);
    }

    private function fillCart(): ProductVariant
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 1000,
            'stock' => 5,
        ]);

        $this->postForm('/cart', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->keepSession();

        return $variant;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'billing_name' => 'Guest Buyer',
            'billing_email' => 'guest@example.com',
            'billing_phone' => '9999999999',
            'billing_address_line1' => 'Test street',
            'billing_city' => 'Delhi',
            'billing_state' => 'Delhi',
            'billing_postal_code' => '110001',
            'billing_country' => 'IN',
            'payment_method' => 'cod',
            'shipping_rate' => 49,
            'courier_name' => 'Fake Express',
            'estimated_delivery_days' => 3,
        ], $overrides);
    }

    public function test_guest_reaches_the_checkout_page(): void
    {
        $this->fillCart();

        $this->get('/checkout')->assertOk()->assertSee('Billing details');
    }

    public function test_unverified_user_reaches_the_checkout_page(): void
    {
        $user = User::factory()->unverified()->create(['is_active' => true]);
        $this->actingAs($user);
        $this->fillCart();

        $this->get('/checkout')->assertOk();
    }

    public function test_guest_order_creates_an_account_and_signs_them_in(): void
    {
        $variant = $this->fillCart();

        $this->postForm('/checkout', $this->payload())
            ->assertOk()
            ->assertJsonPath('success', true);

        $user = User::where('email', 'guest@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_customer);
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'customer_email' => 'guest@example.com',
            'payment_method' => 'cod',
        ]);
        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'city' => 'Delhi',
        ]);
        $this->assertEquals(4, $variant->fresh()->stock);

        // The generated password is throwaway, so a set-password token is issued.
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'guest@example.com']);
    }

    public function test_failed_order_creates_no_account(): void
    {
        $variant = $this->fillCart();
        $variant->update(['stock' => 0]);

        $this->postForm('/checkout', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('users', ['email' => 'guest@example.com']);
        $this->assertGuest();
    }

    public function test_order_for_a_known_email_is_filed_under_that_account_without_signing_in(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com', 'is_active' => true]);
        Address::create([
            'user_id' => $owner->id,
            'label' => 'Home',
            'name' => $owner->name,
            'phone' => '8888888888',
            'address_line1' => 'Owner street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country' => 'IN',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);

        $this->fillCart();

        $this->postForm('/checkout', $this->payload(['billing_email' => 'owner@example.com']))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertGuest();
        $this->assertDatabaseHas('orders', [
            'user_id' => $owner->id,
            'customer_email' => 'owner@example.com',
        ]);

        // The saved address of an account nobody signed into stays untouched.
        $this->assertDatabaseHas('addresses', [
            'user_id' => $owner->id,
            'city' => 'Mumbai',
        ]);
        $this->assertEquals(1, $owner->addresses()->count());
    }

    public function test_success_page_is_visible_to_whoever_placed_the_order_and_nobody_else(): void
    {
        User::factory()->create(['email' => 'owner@example.com', 'is_active' => true]);
        $this->fillCart();

        $this->postForm('/checkout', $this->payload(['billing_email' => 'owner@example.com']))->assertOk();

        $this->keepSession();
        $order = Order::firstOrFail();

        // Same (guest) session that placed it.
        $this->get(route('shop.checkout.success', $order->order_number))->assertOk();

        // Anyone else guessing the order number gets nothing. Both halves are
        // needed: a different cookie, and a flush of the session store the test
        // process shares between requests.
        $this->flushSession();
        $this->withCookie(config('session.cookie'), Str::random(40));
        $this->get(route('shop.checkout.success', $order->order_number))->assertNotFound();
    }

    public function test_signed_in_customer_still_checks_out_against_their_own_account(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);
        $variant = $this->fillCart();

        $this->postForm('/checkout', $this->payload(['billing_email' => $user->email]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'customer_email' => $user->email,
        ]);
        $this->assertEquals(4, $variant->fresh()->stock);
        $this->assertEquals(1, User::count());
    }
}
