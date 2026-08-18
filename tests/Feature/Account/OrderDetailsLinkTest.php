<?php

namespace Tests\Feature\Account;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailsLinkTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(User $user, string $number = 'ELP-ACC001'): Order
    {
        $order = Order::create([
            'order_number' => $number,
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

    private function customer(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);
    }

    public function test_the_dashboard_links_each_recent_order_to_its_details(): void
    {
        $user = $this->customer();
        $order = $this->orderFor($user);

        $response = $this->actingAs($user)->get('/account');

        $response->assertOk();
        $response->assertSee('ELP-ACC001');
        $response->assertSee('View details');
        $response->assertSee(route('shop.checkout.success', $order->order_number), false);
    }

    public function test_following_the_link_opens_the_order(): void
    {
        $user = $this->customer();
        $order = $this->orderFor($user);

        $response = $this->actingAs($user)->get(route('shop.checkout.success', $order->order_number));

        $response->assertOk();
        $response->assertSee('ELP-ACC001');
        $response->assertSee('Garam Masala');
    }

    public function test_a_customer_cannot_open_someone_elses_order(): void
    {
        $owner = $this->customer();
        $order = $this->orderFor($owner);

        $intruder = $this->customer();

        $this->actingAs($intruder)
            ->get(route('shop.checkout.success', $order->order_number))
            ->assertNotFound();
    }

    public function test_the_empty_state_survives_with_no_orders(): void
    {
        $this->actingAs($this->customer())
            ->get('/account')
            ->assertOk()
            ->assertSee('No orders yet.')
            ->assertDontSee('View details');
    }
}
