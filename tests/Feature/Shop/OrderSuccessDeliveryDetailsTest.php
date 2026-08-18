<?php

namespace Tests\Feature\Shop;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OrderSuccessDeliveryDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(User $user): Order
    {
        $order = Order::create([
            'order_number' => 'ELP-TEST99',
            'user_id' => $user->id,
            'customer_name' => 'Amrit',
            'customer_email' => $user->email,
            'shipping_name' => 'Amrit',
            'shipping_address' => '12 Connaught Place',
            'shipping_city' => 'New Delhi',
            'shipping_state' => 'Delhi',
            'shipping_postal_code' => '110001',
            'subtotal' => 500,
            'shipping_amount' => 49,
            'tax_amount' => 90,
            'tax_percent' => 18,
            'total' => 639,
            'currency' => 'INR',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'courier_name' => 'Fake Express',
            'estimated_delivery_days' => 4,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Garam Masala',
            'product_sku' => 'GM-100',
            'quantity' => 1,
            'price' => 500,
            'total' => 500,
        ]);

        return $order;
    }

    private function visitSuccessPage(): TestResponse
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);
        $order = $this->orderFor($user);

        return $this->actingAs($user)->get('/checkout/success/'.$order->order_number);
    }

    public function test_courier_and_estimate_are_hidden_by_default(): void
    {
        $response = $this->visitSuccessPage();

        $response->assertOk();
        $response->assertSee('Order Confirmed!');
        $response->assertDontSee('Fake Express');
        $response->assertDontSee('Estimated delivery');
    }

    public function test_enabling_the_setting_shows_them(): void
    {
        app(SettingsService::class)->set('shipping', 'show_delivery_details', true, 'boolean');

        $response = $this->visitSuccessPage();

        $response->assertOk();
        $response->assertSee('Fake Express');
        $response->assertSee('Estimated delivery');
    }
}
