<?php

namespace Tests\Feature\Shop;

use App\Models\EmailTemplate;
use App\Services\Mail\TemplateMailer;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = app(SettingsService::class);
        $this->settings->set('notifications', 'enabled', true, 'boolean');

        EmailTemplate::updateOrCreate(['slug' => 'order_placed_admin'], [
            'name' => 'Order Placed (Admin Copy)',
            'subject' => 'New order {{order_number}}',
            'body' => '<p>{{customer_email}}</p>',
            'placeholders' => ['order_number', 'customer_email'],
            'is_active' => true,
        ]);
    }

    /**
     * The suite runs on the "array" mailer, whose transport keeps every message.
     *
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

    /**
     * @return array<int, string>
     */
    private function recipientsOf(SentMessage $message): array
    {
        return array_map(fn ($address) => $address->getAddress(), $message->getEnvelope()->getRecipients());
    }

    public function test_admin_recipients_are_parsed_from_a_delimited_list(): void
    {
        $this->settings->set('email', 'admin_email', 'owner@shop.test, ops@shop.test; owner@shop.test, nope');

        $this->assertSame(
            ['owner@shop.test', 'ops@shop.test'],
            app(TemplateMailer::class)->adminRecipients()
        );
    }

    public function test_admin_recipients_fall_back_to_the_support_email(): void
    {
        $this->settings->set('email', 'admin_email', '');
        $this->settings->set('commerce', 'support_email', 'help@shop.test');

        $this->assertSame(['help@shop.test'], app(TemplateMailer::class)->adminRecipients());
    }

    public function test_each_recipient_gets_their_own_message(): void
    {
        $sent = app(TemplateMailer::class)->send(
            'order_placed_admin',
            ['owner@shop.test', 'ops@shop.test'],
            ['order_number' => 'ORD-1', 'customer_email' => 'buyer@example.com'],
        );

        $this->assertTrue($sent);

        // One message each, so neither address is disclosed to the other.
        $messages = $this->sentMessages();
        $this->assertCount(2, $messages);
        $this->assertSame(['owner@shop.test'], $this->recipientsOf($messages[0]));
        $this->assertSame(['ops@shop.test'], $this->recipientsOf($messages[1]));

        $this->assertSame('New order ORD-1', $messages[0]->getOriginalMessage()->getSubject());
        $this->assertStringContainsString('buyer@example.com', $this->htmlBody($messages[0]));
    }

    public function test_disabled_admin_notification_stops_the_copy(): void
    {
        $this->settings->set('notifications', 'notify_order_placed_admin', false, 'boolean');

        $sent = app(TemplateMailer::class)->send('order_placed_admin', ['owner@shop.test'], []);

        $this->assertFalse($sent);
        $this->assertCount(0, $this->sentMessages());
    }

    public function test_no_admin_address_sends_nothing(): void
    {
        $this->assertFalse(app(TemplateMailer::class)->send('order_placed_admin', [], []));
        $this->assertCount(0, $this->sentMessages());
    }

    public function test_gmail_settings_resolve_to_a_starttls_smtp_transport(): void
    {
        $this->settings->setMany('email', [
            'mailer' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'shop@gmail.com',
            'password' => 'app-password',
            'from_address' => 'shop@gmail.com',
        ], ['port' => ['type' => 'integer']]);

        app(TemplateMailer::class)->applyMailConfig();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.gmail.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertSame('shop@gmail.com', config('mail.from.address'));
    }

    public function test_port_465_resolves_to_implicit_tls(): void
    {
        $this->settings->setMany('email', [
            'mailer' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'encryption' => 'ssl',
        ], ['port' => ['type' => 'integer']]);

        app(TemplateMailer::class)->applyMailConfig();

        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
    }
}
