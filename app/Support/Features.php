<?php

namespace App\Support;

use App\Services\Settings\SettingsService;

/**
 * Feature flags stored in the `features` settings group and toggled from
 * Admin > Settings. Defaults keep both features on for existing installs.
 */
class Features
{
    public const DEFAULTS = [
        'email_verification' => true,
        'password_reset' => true,
    ];

    public static function enabled(string $key): bool
    {
        return app(SettingsService::class)->bool('features', $key, self::DEFAULTS[$key] ?? false);
    }

    public static function emailVerification(): bool
    {
        return self::enabled('email_verification');
    }

    public static function passwordReset(): bool
    {
        return self::enabled('password_reset');
    }
}
