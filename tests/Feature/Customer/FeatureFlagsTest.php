<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FeatureFlagsTest extends TestCase
{
    use RefreshDatabase;

    private function flag(string $key, bool $value): void
    {
        app(SettingsService::class)->set('features', $key, $value, 'boolean');
    }

    public function test_password_reset_routes_available_by_default(): void
    {
        $this->get('/forgot-password')->assertOk();
        $this->get('/login')->assertSee('Forgot password?');
    }

    public function test_password_reset_routes_disabled_by_flag(): void
    {
        $this->flag('password_reset', false);

        $this->get('/forgot-password')->assertNotFound();
        $this->postJson('/forgot-password', ['email' => 'a@b.com'])->assertNotFound();
        $this->get('/reset-password/token123')->assertNotFound();
        $this->postJson('/reset-password', [])->assertNotFound();
        $this->get('/login')->assertDontSee('Forgot password?');
    }

    public function test_email_verification_routes_disabled_by_flag(): void
    {
        $this->flag('email_verification', false);
        $user = User::factory()->unverified()->create(['is_active' => true]);

        $this->actingAs($user)->get('/email/verify')->assertNotFound();
        $this->actingAs($user)->postJson('/email/verification-notification')->assertNotFound();
    }

    public function test_checkout_ignores_the_verification_flag(): void
    {
        // Checkout is open to guests, so email verification cannot gate it either
        // way: both settings land on the empty-cart redirect, not /email/verify.
        $user = User::factory()->unverified()->create(['is_active' => true]);
        $this->actingAs($user)->get('/checkout')->assertRedirect(route('shop.cart'));

        $this->flag('email_verification', false);
        $this->actingAs($user)->get('/checkout')->assertRedirect(route('shop.cart'));
    }

    public function test_registration_skips_verification_when_disabled(): void
    {
        Notification::fake();
        $this->flag('email_verification', false);

        $this->postJson('/register', [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '9999999999',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
        ])->assertOk()->assertJsonPath('redirect', route('account.dashboard'));

        Notification::assertNothingSent();
    }

    public function test_registration_sends_verification_when_enabled(): void
    {
        Notification::fake();

        $this->postJson('/register', [
            'name' => 'Test Customer',
            'email' => 'customer2@example.com',
            'phone' => '9999999999',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
        ])->assertOk()->assertJsonPath('redirect', route('verification.notice'));

        Notification::assertSentTo(
            User::where('email', 'customer2@example.com')->first(),
            \Illuminate\Auth\Notifications\VerifyEmail::class
        );
    }
}
