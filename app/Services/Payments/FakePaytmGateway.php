<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Str;

class FakePaytmGateway implements PaymentGateway
{
    public function initiate(Order $order): array
    {
        $txn = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'paytm',
            'gateway_order_id' => $order->order_number,
            'amount' => $order->total,
            'currency' => $order->currency ?: 'INR',
            'status' => 'pending',
            'request_payload' => ['mode' => 'fake'],
        ]);

        return [
            'success' => true,
            'mode' => 'fake',
            'transaction_id' => $txn->id,
            'redirect_url' => route('payments.paytm.fake', $txn),
            'txn_token' => 'FAKE-'.Str::upper(Str::random(12)),
        ];
    }

    public function verifyCallback(array $payload): PaymentTransaction
    {
        $txn = PaymentTransaction::findOrFail($payload['transaction_id'] ?? 0);

        if (($payload['STATUS'] ?? '') === 'TXN_SUCCESS') {
            $txn->update([
                'status' => 'paid',
                'transaction_id' => $payload['TXNID'] ?? ('FAKE-TXN-'.$txn->id),
                'response_payload' => $payload,
                'paid_at' => now(),
            ]);
            $txn->order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);
        } else {
            $txn->update([
                'status' => 'failed',
                'response_payload' => $payload,
            ]);
            $txn->order->update(['payment_status' => 'failed']);
        }

        return $txn->fresh('order');
    }

    public function reconcile(PaymentTransaction $transaction): PaymentTransaction
    {
        return $transaction;
    }

    public function testConnection(): array
    {
        return ['success' => true, 'message' => 'Fake Paytm gateway is ready.'];
    }
}
