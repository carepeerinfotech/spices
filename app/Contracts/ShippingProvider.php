<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Shipment;

interface ShippingProvider
{
    public function testConnection(): array;

    public function serviceability(string $pickupPincode, string $deliveryPincode, float $weightKg, float $codAmount = 0): array;

    public function createOrder(Order $order): Shipment;

    public function assignAwb(Shipment $shipment, ?string $courierId = null): Shipment;

    public function generatePickup(Shipment $shipment): Shipment;

    public function track(Shipment $shipment): array;

    public function cancel(Shipment $shipment): Shipment;

    public function createReturn(Shipment $shipment): Shipment;

    public function pickupLocations(): array;
}
