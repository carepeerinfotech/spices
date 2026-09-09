<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use App\Models\Order;
use App\Models\Shipment;
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

    /**
     * Create (or, if one already exists, update) the Shiprocket order for the
     * given order. Shared by the admin "send to Shiprocket" action and by the
     * automatic push that happens once an order is confirmed.
     */
    public function pushOrder(Order $order): Shipment
    {
        $order->loadMissing('items', 'shipment');

        return $order->shipment
            ? $this->driver()->updateOrder($order, $order->shipment)
            : $this->driver()->createOrder($order);
    }
}
