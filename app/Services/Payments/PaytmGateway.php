<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Http;
use paytm\paytmchecksum\PaytmChecksum;

class PaytmGateway implements PaymentGateway
{
    public function __construct(private SettingsService $settings) {}

    public function initiate(Order $order): array
    {
        $mid = (string) $this->settings->get('paytm', 'merchant_id');
        $key = (string) $this->settings->get('paytm', 'merchant_key');
        $website = (string) $this->settings->get('paytm', 'website', 'WEBSTAGING');
        $env = (string) $this->settings->get('paytm', 'environment', 'staging');

        if (! $mid || ! $key) {
            throw new \RuntimeException('Paytm is not configured.');
        }

        $callback = route('payments.paytm.callback');
        $body = [
            'requestType' => 'Payment',
            'mid' => $mid,
            'websiteName' => $website,
            'orderId' => $order->order_number,
            'callbackUrl' => $callback,
            'txnAmount' => [
                'value' => number_format((float) $order->total, 2, '.', ''),
                'currency' => 'INR',
            ],
            'userInfo' => [
                'custId' => (string) ($order->user_id ?: 'GUEST-'.$order->id),
                'email' => $order->customer_email,
                'mobile' => $order->customer_phone,
            ],
        ];

        $signature = PaytmChecksum::generateSignature(json_encode($body, JSON_UNESCAPED_SLASHES), $key);
        $host = $env === 'production'
            ? 'https://secure.paytmpayments.com'
            : 'https://securestage.paytmpayments.com';

        $response = Http::asJson()->post(
            "{$host}/theia/api/v1/initiateTransaction?mid={$mid}&orderId={$order->order_number}",
            ['body' => $body, 'head' => ['signature' => $signature]]
        )->json();

        $txnToken = data_get($response, 'body.txnToken');
        if (! $txnToken) {
            throw new \RuntimeException(data_get($response, 'body.resultInfo.resultMsg', 'Unable to initiate Paytm payment.'));
        }

        $txn = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'paytm',
            'gateway_order_id' => $order->order_number,
            'amount' => $order->total,
            'currency' => 'INR',
            'status' => 'pending',
            'request_payload' => $body,
            'response_payload' => $response,
            'checksum' => $signature,
        ]);

        return [
            'success' => true,
            'mode' => 'live',
            'transaction_id' => $txn->id,
            'mid' => $mid,
            'order_id' => $order->order_number,
            'txn_token' => $txnToken,
            'redirect_url' => "{$host}/theia/api/v1/showPaymentPage?mid={$mid}&orderId={$order->order_number}",
        ];
    }

    public function verifyCallback(array $payload): PaymentTransaction
    {
        $key = (string) $this->settings->get('paytm', 'merchant_key');
        $orderId = $payload['ORDERID'] ?? null;
        $checksum = $payload['CHECKSUMHASH'] ?? '';

        $txn = PaymentTransaction::where('gateway_order_id', $orderId)->latest()->firstOrFail();

        if ($txn->status === 'paid') {
            return $txn;
        }

        $verifyPayload = $payload;
        unset($verifyPayload['CHECKSUMHASH']);
        $valid = PaytmChecksum::verifySignature($verifyPayload, $key, $checksum);

        if (! $valid) {
            $txn->update(['status' => 'failed', 'response_payload' => $payload]);
            throw new \RuntimeException('Invalid Paytm checksum.');
        }

        if (($payload['STATUS'] ?? '') === 'TXN_SUCCESS') {
            $txn->update([
                'status' => 'paid',
                'transaction_id' => $payload['TXNID'] ?? null,
                'response_payload' => $payload,
                'checksum' => $checksum,
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
                'checksum' => $checksum,
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
        $mid = $this->settings->get('paytm', 'merchant_id');
        $key = $this->settings->get('paytm', 'merchant_key');

        if (! $mid || ! $key) {
            return ['success' => false, 'message' => 'Merchant ID and Key are required.'];
        }

        return ['success' => true, 'message' => 'Paytm credentials are present for '.$this->settings->get('paytm', 'environment', 'staging').' environment.'];
    }
}
