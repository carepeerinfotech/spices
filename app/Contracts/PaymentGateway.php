<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\PaymentTransaction;

interface PaymentGateway
{
    public function initiate(Order $order): array;

    public function verifyCallback(array $payload): PaymentTransaction;

    public function reconcile(PaymentTransaction $transaction): PaymentTransaction;

    public function testConnection(): array;
}
