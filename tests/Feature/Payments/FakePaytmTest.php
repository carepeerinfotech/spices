<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakePaytmTest extends TestCase
{
    use RefreshDatabase;

    public function test_fake_paytm_success_marks_order_paid(): void
    {
        app(SettingsService::class)->set('paytm', 'driver', 'fake');
        app(SettingsService::class)->set('notifications', 'enabled', false, 'boolean');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $order = Order::create([
            'order_number' => 'ELP-TEST01',
            'user_id' => $user->id,
            'customer_name' => 'Buyer',
            'customer_email' => $user->email,
            'shipping_address' => 'A',
            'shipping_city' => 'Delhi',
            'shipping_state' => 'Delhi',
            'shipping_postal_code' => '110001',
            'shipping_country' => 'IN',
            'subtotal' => 100,
            'shipping_amount' => 0,
            'tax_amount' => 18,
            'total' => 118,
            'currency' => 'INR',
            'payment_method' => 'paytm',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $txn = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'paytm',
            'gateway_order_id' => $order->order_number,
            'amount' => 118,
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post(route('payments.paytm.fake.complete', $txn), [
            'status' => 'success',
        ])->assertRedirect(route('shop.checkout.success', $order->order_number));

        $this->assertEquals('paid', $txn->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }
}
