<?php

namespace App\Services\Mail;

use App\Models\EmailTemplate;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class TemplateMailer
{
    public function __construct(private SettingsService $settings) {}

    public function applyMailConfig(): void
    {
        $mail = $this->settings->group('email');
        if (empty($mail)) {
            return;
        }

        Config::set('mail.default', $mail['mailer'] ?? 'smtp');
        Config::set('mail.mailers.smtp.host', $mail['host'] ?? '127.0.0.1');
        Config::set('mail.mailers.smtp.port', (int) ($mail['port'] ?? 587));
        Config::set('mail.mailers.smtp.username', $mail['username'] ?? null);
        Config::set('mail.mailers.smtp.password', $mail['password'] ?? null);
        Config::set('mail.mailers.smtp.encryption', $mail['encryption'] ?? 'tls');
        Config::set('mail.from.address', $mail['from_address'] ?? config('mail.from.address'));
        Config::set('mail.from.name', $mail['from_name'] ?? config('mail.from.name'));
    }

    public function send(string $slug, string $to, array $data = []): bool
    {
        if (! $this->settings->bool('notifications', 'enabled', true)) {
            return false;
        }

        $eventKey = 'notify_'.$slug;
        if (! $this->settings->bool('notifications', $eventKey, true)) {
            return false;
        }

        $template = EmailTemplate::query()->where('slug', $slug)->where('is_active', true)->first();
        if (! $template) {
            return false;
        }

        $this->applyMailConfig();
        $rendered = $template->render($data);

        Mail::html($rendered['body'], function ($message) use ($to, $rendered) {
            $message->to($to)->subject($rendered['subject']);
        });

        return true;
    }
}
