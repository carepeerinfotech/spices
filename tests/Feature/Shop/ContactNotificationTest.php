<?php

namespace Tests\Feature\Shop;

use App\Models\ContactMessage;
use App\Models\EmailTemplate;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

class ContactNotificationTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = app(SettingsService::class);
        $this->settings->set('notifications', 'enabled', true, 'boolean');
        $this->settings->set('email', 'admin_email', 'owner@shop.test');

        EmailTemplate::updateOrCreate(['slug' => 'contact_message_admin'], [
            'name' => 'Contact Enquiry (Admin)',
            'subject' => 'New enquiry from {{name}}',
            'body' => '<p>{{email}} / {{phone}}</p><p>{{message}}</p>',
            'placeholders' => ['name', 'email', 'phone', 'message', 'received_at'],
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

    /**
     * The decoded HTML body. Reading toString() instead would assert against
     * quoted-printable, where soft line breaks split words at column 76.
     */
    private function htmlBody(SentMessage $message): string
    {
        return (string) $message->getOriginalMessage()->getHtmlBody();
    }

    private function submit(array $overrides = []): TestResponse
    {
        return $this->post('/contact', [
            'name' => 'Amrit',
            'email' => 'visitor@example.com',
            'phone' => '9876543210',
            'message' => 'Do you ship to Canada?',
            ...$overrides,
        ]);
    }

    public function test_a_new_enquiry_emails_the_admin(): void
    {
        $this->submit()->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', ['email' => 'visitor@example.com']);

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);

        $sent = $messages[0];
        $this->assertSame('owner@shop.test', $sent->getEnvelope()->getRecipients()[0]->getAddress());
        $this->assertSame('New enquiry from Amrit', $sent->getOriginalMessage()->getSubject());

        $body = $this->htmlBody($sent);
        $this->assertStringContainsString('visitor@example.com', $body);
        $this->assertStringContainsString('Do you ship to Canada?', $body);
    }

    public function test_a_missing_phone_is_labelled_rather_than_left_blank(): void
    {
        $this->submit(['phone' => null]);

        $this->assertStringContainsString('Not provided', $this->htmlBody($this->sentMessages()[0]));
    }

    public function test_visitor_html_is_escaped_before_it_reaches_the_admin_inbox(): void
    {
        $this->submit(['message' => 'Hi <script>alert(1)</script> there']);

        $body = $this->htmlBody($this->sentMessages()[0]);
        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
    }

    public function test_disabling_the_toggle_stops_the_alert_but_still_saves_the_message(): void
    {
        $this->settings->set('notifications', 'notify_contact_message_admin', false, 'boolean');

        $this->submit()->assertSessionHas('success');

        $this->assertSame(1, ContactMessage::count());
        $this->assertCount(0, $this->sentMessages());
    }

    public function test_no_admin_address_still_saves_the_message(): void
    {
        $this->settings->set('email', 'admin_email', '');
        $this->settings->set('commerce', 'support_email', '');

        $this->submit()->assertSessionHas('success');

        $this->assertSame(1, ContactMessage::count());
        $this->assertCount(0, $this->sentMessages());
    }
}
