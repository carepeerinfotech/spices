<?php

namespace Tests\Feature\Shop;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_merges_into_user_cart(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
            'price' => 200,
        ]);

        $guestSessionId = 'guest-session-abc';
        $guestCart = Cart::create([
            'session_id' => $guestSessionId,
            'user_id' => null,
        ]);
        CartItem::create([
            'cart_id' => $guestCart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 200,
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $merged = app(CartService::class)->mergeSessionCartIntoUser($user->id, $guestSessionId);

        $this->assertEquals($user->id, $merged->user_id);
        $this->assertEquals(2, $merged->itemCount());
        $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $merged->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_login_endpoint_succeeds_for_active_customer(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('success', true);
    }
}
