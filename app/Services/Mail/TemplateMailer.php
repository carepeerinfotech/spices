<?php

namespace App\Services\Mail;

use App\Models\EmailTemplate;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class TemplateMailer
{
    public function __construct(private SettingsService $settings) {}

    public function applyMailConfig(): void
    {
        $mail = $this->settings->group('email');
        if (empty($mail)) {
            return;
        }

        // Anything left blank in the admin keeps whatever config/.env already holds,
        // so a half-filled settings group never forces SMTP over the configured mailer.
        $value = fn (string $key, mixed $fallback) => filled($mail[$key] ?? null) ? $mail[$key] : $fallback;

        $port = (int) $value('port', config('mail.mailers.smtp.port', 587));
        $encryption = strtolower(trim((string) $value('encryption', 'tls')));

        Config::set('mail.default', $value('mailer', config('mail.default')));
        Config::set('mail.mailers.smtp.host', $value('host', config('mail.mailers.smtp.host')));
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $value('username', config('mail.mailers.smtp.username')));
        Config::set('mail.mailers.smtp.password', $value('password', config('mail.mailers.smtp.password')));
        Config::set('mail.mailers.smtp.encryption', $encryption);
        // Symfony picks implicit TLS from the scheme; "smtp" still upgrades via
        // STARTTLS when the server offers it (Gmail on 587).
        Config::set('mail.mailers.smtp.scheme', ($encryption === 'ssl' || $port === 465) ? 'smtps' : 'smtp');
        Config::set('mail.from.address', $value('from_address', config('mail.from.address')));
        Config::set('mail.from.name', $value('from_name', config('mail.from.name')));

        // Drop any mailer already built from the previous config.
        Mail::purge('smtp');
    }

    /**
     * Store owner addresses that should receive a copy of order notifications.
     *
     * @return array<int, string>
     */
    public function adminRecipients(): array
    {
        $configured = (string) ($this->settings->get('email', 'admin_email')
            ?: $this->settings->get('commerce', 'support_email', ''));

        $emails = preg_split('/[,;]+/', $configured) ?: [];
        $emails = array_filter(array_map('trim', $emails), fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

        return array_values(array_unique($emails));
    }

    /**
     * Drop a template's rendered body into the branded shell. Branding lives in the
     * Blade layout rather than the editable templates, so restyling every mail is
     * one file and an admin cannot break it while editing copy.
     */
    private function wrapInLayout(string $subject, string $body): string
    {
        $commerce = $this->settings->group('commerce');

        return view('emails.layout', [
            'subject' => $subject,
            'content' => $body,
            'storeName' => $commerce['store_name'] ?? config('mail.from.name', 'Elephant Shop'),
            'supportEmail' => $commerce['support_email'] ?? null,
            'logoUrl' => url('assets/images/logo.png'),
            'appUrl' => url('/'),
            'year' => date('Y'),
        ])->render();
    }

    /**
     * @param  string|array<int, string>  $to
     */
    public function send(string $slug, string|array $to, array $data = []): bool
    {
        if (! $this->settings->bool('notifications', 'enabled', true)) {
            return false;
        }

        $eventKey = 'notify_'.$slug;
        if (! $this->settings->bool('notifications', $eventKey, true)) {
            return false;
        }

        $recipients = array_values(array_filter(array_map('trim', (array) $to)));
        if ($recipients === []) {
            return false;
        }

        $template = EmailTemplate::query()->where('slug', $slug)->where('is_active', true)->first();
        if (! $template) {
            return false;
        }

        $this->applyMailConfig();
        $rendered = $template->render($data);

        // What Mail::html() does internally, called directly because MailFake
        // implements send() but not html() and would otherwise hit real SMTP.
        $view = ['html' => new HtmlString($this->wrapInLayout($rendered['subject'], $rendered['body']))];

        // One message per recipient so addresses are never disclosed to each other.
        foreach ($recipients as $recipient) {
            Mail::send($view, [], function ($message) use ($recipient, $rendered) {
                $message->to($recipient)->subject($rendered['subject']);
            });
        }

        return true;
    }
}
