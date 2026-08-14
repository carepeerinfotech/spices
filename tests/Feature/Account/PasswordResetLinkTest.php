<?php

namespace Tests\Feature\Account;

use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * The account page mails a reset link rather than asking for the current
 * password, which an account created during checkout never had.
 */
class PasswordResetLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_customer_can_have_a_reset_link_mailed_to_them(): void
    {
        Notification::fake();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->postJson(route('account.password.link'))
            ->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_guests_cannot_request_a_link(): void
    {
        Notification::fake();

        $this->post(route('account.password.link'))->assertRedirect('/login');

        Notification::assertNothingSent();
    }

    public function test_signed_in_customer_can_open_and_complete_their_own_reset_link(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $token = Password::broker()->createToken($user);
        $link = route('password.reset', ['token' => $token, 'email' => $user->email]);

        // Being signed in must not bounce them off their own link.
        $this->actingAs($user)->get($link)->assertOk()->assertSee('Reset password');

        $this->actingAs($user)->postJson(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect', route('account.dashboard'));

        $this->assertTrue(Hash::check('NewPassword!234', $user->fresh()->password));
    }

    public function test_guest_completing_a_reset_is_sent_to_the_login_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();

        $this->postJson(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ])
            ->assertOk()
            ->assertJsonPath('redirect', route('login'));
    }

    public function test_a_used_link_is_cleared_and_no_longer_opens_the_form(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($user);
        $link = route('password.reset', ['token' => $token, 'email' => $user->email]);

        $this->get($link)->assertOk();

        $this->postJson(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ])->assertOk();

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->get($link)
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('error');
    }

    public function test_a_bogus_link_does_not_open_the_form(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $bogus = str_repeat('a', 64);

        $this->get(route('password.reset', ['token' => $bogus, 'email' => $user->email]))
            ->assertRedirect(route('password.request'));

        // A link with no email on it cannot identify an account either.
        $this->get(route('password.reset', ['token' => $bogus]))
            ->assertRedirect(route('password.request'));

        // A signed-in customer goes back to the page that offers a new link.
        $this->actingAs($user)
            ->get(route('password.reset', ['token' => $bogus, 'email' => $user->email]))
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_account_page_falls_back_to_the_in_place_form_when_reset_is_disabled(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);

        $this->actingAs($user)->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Email me a reset link');

        app(SettingsService::class)->set('features', 'password_reset', false, 'boolean');

        $this->actingAs($user)->get(route('account.dashboard'))
            ->assertOk()
            ->assertDontSee('Email me a reset link')
            ->assertSee('Current password');

        $this->actingAs($user)->postJson(route('account.password.link'))->assertNotFound();
    }
}
