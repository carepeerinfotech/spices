<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Services\Settings\SettingsService;

class PaymentGatewayManager
{
    public function __construct(private SettingsService $settings) {}

    public function paytm(): PaymentGateway
    {
        $driver = $this->settings->get('paytm', 'driver', 'fake');

        return $driver === 'live'
            ? app(PaytmGateway::class)
            : app(FakePaytmGateway::class);
    }
}
