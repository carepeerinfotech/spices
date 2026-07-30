<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Settings\SettingsService;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function index()
    {
        return view('admin.settings.index', [
            'commerce' => $this->settings->group('commerce'),
            'payments' => $this->settings->group('payments'),
            'paytm' => $this->settings->group('paytm'),
            'email' => $this->settings->group('email'),
            'notifications' => $this->settings->group('notifications'),
            'shipping' => $this->settings->group('shipping'),
            'shiprocket' => $this->settings->group('shiprocket'),
            'masked' => [
                'paytm_key' => $this->settings->masked('paytm', 'merchant_key'),
                'email_password' => $this->settings->masked('email', 'password'),
                'shiprocket_password' => $this->settings->masked('shiprocket', 'password'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $group = $request->validate(['group' => ['required', 'string']])['group'];

        match ($group) {
            'commerce' => $this->settings->setMany('commerce', [
                'currency' => $request->input('currency', 'INR'),
                'gst_percent' => (float) $request->input('gst_percent', 18),
                'store_name' => $request->input('store_name', 'Elephant Shop'),
                'support_email' => $request->input('support_email'),
                'pickup_pincode' => $request->input('pickup_pincode', '110001'),
            ], [
                'gst_percent' => ['type' => 'float'],
            ]),
            'payments' => $this->settings->setMany('payments', [
                'cod_enabled' => $request->boolean('cod_enabled'),
                'online_enabled' => $request->boolean('online_enabled'),
            ], [
                'cod_enabled' => ['type' => 'boolean'],
                'online_enabled' => ['type' => 'boolean'],
            ]),
            'paytm' => $this->settings->setMany('paytm', [
                'driver' => $request->input('driver', 'fake'),
                'environment' => $request->input('environment', 'staging'),
                'merchant_id' => $request->input('merchant_id'),
                'merchant_key' => $request->input('merchant_key'),
                'website' => $request->input('website', 'WEBSTAGING'),
                'industry_type' => $request->input('industry_type', 'Retail'),
            ], [
                'merchant_key' => ['encrypted' => true],
            ]),
            'email' => $this->settings->setMany('email', [
                'mailer' => $request->input('mailer', 'smtp'),
                'host' => $request->input('host'),
                'port' => (int) $request->input('port', 587),
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'encryption' => $request->input('encryption', 'tls'),
                'from_address' => $request->input('from_address'),
                'from_name' => $request->input('from_name'),
            ], [
                'port' => ['type' => 'integer'],
                'password' => ['encrypted' => true],
            ]),
            'notifications' => $this->settings->setMany('notifications', [
                'enabled' => $request->boolean('enabled'),
                'notify_order_placed' => $request->boolean('notify_order_placed'),
                'notify_payment_result' => $request->boolean('notify_payment_result'),
                'notify_shipment_update' => $request->boolean('notify_shipment_update'),
                'notify_verify_email' => $request->boolean('notify_verify_email'),
            ], [
                'enabled' => ['type' => 'boolean'],
                'notify_order_placed' => ['type' => 'boolean'],
                'notify_payment_result' => ['type' => 'boolean'],
                'notify_shipment_update' => ['type' => 'boolean'],
                'notify_verify_email' => ['type' => 'boolean'],
            ]),
            'shipping' => $this->settings->setMany('shipping', [
                'charges_enabled' => $request->boolean('charges_enabled'),
                'flat_rate' => (float) $request->input('flat_rate', 49),
                'free_above' => (float) $request->input('free_above', 999),
            ], [
                'charges_enabled' => ['type' => 'boolean'],
                'flat_rate' => ['type' => 'float'],
                'free_above' => ['type' => 'float'],
            ]),
            'shiprocket' => $this->settings->setMany('shiprocket', [
                'enabled' => $request->boolean('enabled'),
                'driver' => $request->input('driver', 'fake'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'pickup_location' => $request->input('pickup_location', 'Primary'),
                'channel_id' => $request->input('channel_id'),
            ], [
                'enabled' => ['type' => 'boolean'],
                'password' => ['encrypted' => true],
            ]),
            default => abort(422, 'Unknown settings group.'),
        };

        return response()->json(['success' => true, 'message' => ucfirst($group).' settings saved.']);
    }

    public function testPaytm(PaymentGatewayManager $payments)
    {
        return response()->json($payments->paytm()->testConnection());
    }

    public function testShiprocket(ShippingManager $shipping)
    {
        return response()->json($shipping->driver()->testConnection());
    }
}
