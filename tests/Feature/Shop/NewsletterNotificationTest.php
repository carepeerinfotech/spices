<?php

namespace Tests\Feature\Shop;

use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

class NewsletterNotificationTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = app(SettingsService::class);
        $this->settings->set('notifications', 'enabled', true, 'boolean');
        $this->settings->set('email', 'admin_email', 'owner@shop.test');

        EmailTemplate::updateOrCreate(['slug' => 'newsletter_signup_admin'], [
            'name' => 'Newsletter Signup (Admin)',
            'subject' => 'New newsletter subscriber',
            'body' => '<p>{{email}} on {{subscribed_at}}. Total: {{total_subscribers}}.</p>',
            'placeholders' => ['email', 'subscribed_at', 'total_subscribers'],
            'is_active' => true,
        ]);
    }

    /**
     * @return array<int, SentMessage>
     */
    private function sentMessages(): array
    {
        return array_values((array) Mail::mailer('array')->getSymfonyTransport()->messages()->all());
    }

    private function htmlBody(SentMessage $message): string
    {
        return (string) $message->getOriginalMessage()->getHtmlBody();
    }

    private function subscribe(string $email = 'reader@example.com'): TestResponse
    {
        return $this->postJson('/newsletter/subscribe', ['email' => $email]);
    }

    public function test_a_new_subscriber_emails_the_admin(): void
    {
        $this->subscribe()->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'reader@example.com']);

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('owner@shop.test', $messages[0]->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertSame('New newsletter subscriber', $messages[0]->getOriginalMessage()->getSubject());

        $body = $this->htmlBody($messages[0]);
        $this->assertStringContainsString('reader@example.com', $body);
        $this->assertStringContainsString('Total: 1.', $body);
    }

    public function test_resubscribing_the_same_address_does_not_alert_again(): void
    {
        $this->subscribe();
        $this->assertCount(1, $this->sentMessages());

        // firstOrCreate returns the existing row, so there is nothing new to report.
        $this->subscribe()->assertOk();

        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertCount(1, $this->sentMessages());
    }

    public function test_the_alert_is_branded_like_the_rest_of_the_mail(): void
    {
        $this->subscribe();

        $body = $this->htmlBody($this->sentMessages()[0]);

        $this->assertStringContainsString('#b82125', $body);
        $this->assertStringContainsString('assets/images/logo.png', $body);
    }

    public function test_disabling_the_toggle_stops_the_alert_but_still_subscribes(): void
    {
        $this->settings->set('notifications', 'notify_newsletter_signup_admin', false, 'boolean');

        $this->subscribe()->assertOk();

        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertCount(0, $this->sentMessages());
    }

    public function test_no_admin_address_still_subscribes(): void
    {
        $this->settings->set('email', 'admin_email', '');
        $this->settings->set('commerce', 'support_email', '');

        $this->subscribe()->assertOk();

        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertCount(0, $this->sentMessages());
    }

    public function test_an_invalid_address_is_rejected_without_mailing(): void
    {
        $this->postJson('/newsletter/subscribe', ['email' => 'not-an-email'])
            ->assertStatus(422);

        $this->assertSame(0, NewsletterSubscriber::count());
        $this->assertCount(0, $this->sentMessages());
    }
}
