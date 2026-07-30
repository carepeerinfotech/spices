<?php

namespace Tests\Feature\Shipping;

use App\Services\Shipping\FakeShiprocketProvider;
use Tests\TestCase;

class ShiprocketFakeTest extends TestCase
{
    public function test_serviceability_returns_rate_for_valid_pincode(): void
    {
        $provider = new FakeShiprocketProvider;
        $result = $provider->serviceability('110001', '400001', 1.0);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['couriers']);
        $this->assertArrayHasKey('rate', $result['cheapest']);
    }

    public function test_unserviceable_pincode(): void
    {
        $provider = new FakeShiprocketProvider;
        $result = $provider->serviceability('110001', '000111', 1.0);

        $this->assertFalse($result['success']);
    }
}
