<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\Mail\TemplateMailer;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentGatewayManager $payments,
        private TemplateMailer $mailer,
    ) {}

    public function fakePaytm(PaymentTransaction $transaction)
    {
        abort_unless($transaction->gateway === 'paytm', 404);

        return view('shop.checkout.paytm-fake', [
            'transaction' => $transaction->load('order'),
            'cartCount' => 0,
        ]);
    }

    public function fakePaytmComplete(Request $request, PaymentTransaction $transaction)
    {
        $data = $request->validate([
            'status' => ['required', 'in:success,failed'],
        ]);

        $payload = [
            'transaction_id' => $transaction->id,
            'ORDERID' => $transaction->gateway_order_id,
            'STATUS' => $data['status'] === 'success' ? 'TXN_SUCCESS' : 'TXN_FAILURE',
            'TXNID' => 'FAKE-'.uniqid(),
        ];

        $txn = $this->payments->paytm()->verifyCallback($payload);
        $this->mailer->send('payment_result', $txn->order->customer_email, [
            'customer_name' => $txn->order->customer_name,
            'order_number' => $txn->order->order_number,
            'status' => $txn->status,
            'total' => '₹'.number_format((float) $txn->order->total, 2),
        ]);

        if ($txn->status === 'paid') {
            return redirect()->route('shop.checkout.success', $txn->order->order_number)
                ->with('success', 'Payment successful.');
        }

        return redirect()->route('shop.checkout')->with('error', 'Payment failed. Please try again.');
    }

    public function paytmCallback(Request $request)
    {
        $txn = $this->payments->paytm()->verifyCallback($request->all());

        $this->mailer->send('payment_result', $txn->order->customer_email, [
            'customer_name' => $txn->order->customer_name,
            'order_number' => $txn->order->order_number,
            'status' => $txn->status,
            'total' => '₹'.number_format((float) $txn->order->total, 2),
        ]);

        if ($txn->status === 'paid') {
            return redirect()->route('shop.checkout.success', $txn->order->order_number);
        }

        return redirect()->route('shop.cart')->with('error', 'Payment failed.');
    }
}
