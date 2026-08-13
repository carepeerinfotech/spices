<?php

namespace Tests\Feature\Admin;

use App\Models\NewsletterSubscriber;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriberAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An admin-role user, optionally granted contact-messages.manage.
     */
    private function admin(bool $withPermission = true): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Store administration', 'is_active' => true]
        );

        if ($withPermission) {
            $permission = Permission::firstOrCreate(
                ['slug' => 'contact-messages.manage'],
                ['name' => 'Manage Contact Messages', 'group' => 'CMS']
            );
            $role->permissions()->sync([$permission->id]);
        }

        $user = User::factory()->create([
            'is_active' => true,
            'is_customer' => false,
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    private function subscriber(string $email, ?string $createdAt = null): NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::create(['email' => $email]);

        // created_at is guarded, so backdating needs an explicit write.
        if ($createdAt) {
            $subscriber->forceFill(['created_at' => $createdAt])->save();
        }

        return $subscriber;
    }

    public function test_the_list_shows_subscribers_newest_first(): void
    {
        $this->subscriber('older@example.com', now()->subDay()->toDateTimeString());
        $this->subscriber('newer@example.com');

        $response = $this->actingAs($this->admin())->get('/admin/newsletter-subscribers');

        $response->assertOk();
        $response->assertSee('older@example.com');
        $response->assertSee('newer@example.com');
        $response->assertSeeInOrder(['newer@example.com', 'older@example.com']);
        $response->assertSee('2 subscribers');
    }

    public function test_the_empty_state_is_shown_with_no_subscribers(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/newsletter-subscribers')
            ->assertOk()
            ->assertSee('No subscribers yet.');
    }

    public function test_the_list_can_be_searched_by_email(): void
    {
        $this->subscriber('keep@example.com');
        $this->subscriber('other@elsewhere.test');

        $response = $this->actingAs($this->admin())->get('/admin/newsletter-subscribers?q=example.com');

        $response->assertOk();
        $response->assertSee('keep@example.com');
        $response->assertDontSee('other@elsewhere.test');
    }

    public function test_a_subscriber_can_be_removed(): void
    {
        $subscriber = $this->subscriber('bye@example.com');

        $this->actingAs($this->admin())
            ->deleteJson('/admin/newsletter-subscribers/'.$subscriber->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('newsletter_subscribers', ['email' => 'bye@example.com']);
    }

    public function test_the_export_streams_every_subscriber_as_csv(): void
    {
        $this->subscriber('one@example.com');
        $this->subscriber('two@example.com');

        $response = $this->actingAs($this->admin())->get('/admin/newsletter-subscribers/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Email,"Subscribed at"', $csv);
        $this->assertStringContainsString('one@example.com', $csv);
        $this->assertStringContainsString('two@example.com', $csv);
    }

    public function test_a_guest_cannot_reach_the_list(): void
    {
        $this->get('/admin/newsletter-subscribers')->assertRedirect();
    }

    public function test_an_admin_without_the_permission_is_refused(): void
    {
        $this->actingAs($this->admin(withPermission: false))
            ->get('/admin/newsletter-subscribers')
            ->assertForbidden();
    }

    public function test_a_signed_in_customer_is_bounced_to_the_admin_login(): void
    {
        $customer = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);

        $this->actingAs($customer)
            ->get('/admin/newsletter-subscribers')
            ->assertRedirect(route('admin.login'));
    }
}
