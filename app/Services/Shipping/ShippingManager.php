<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use App\Services\Settings\SettingsService;

class ShippingManager
{
    public function __construct(private SettingsService $settings) {}

    public function driver(): ShippingProvider
    {
        if (! $this->settings->bool('shiprocket', 'enabled', false)) {
            return app(FakeShiprocketProvider::class);
        }

        $driver = $this->settings->get('shiprocket', 'driver', 'fake');

        return $driver === 'live'
            ? app(ShiprocketProvider::class)
            : app(FakeShiprocketProvider::class);
    }
}
