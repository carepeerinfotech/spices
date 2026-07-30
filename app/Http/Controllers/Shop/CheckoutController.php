<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\Mail\TemplateMailer;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Settings\SettingsService;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private SettingsService $settings,
        private ShippingManager $shipping,
        private PaymentGatewayManager $payments,
        private TemplateMailer $mailer,
    ) {}

    public function index()
    {
        $user = auth()->user()->load('addresses');
        $cart = $this->cartService->getCart();
        $summary = $this->cartService->summary($cart);

        if ($summary['item_count'] < 1) {
            return redirect()->route('shop.cart')->with('error', 'Your cart is empty.');
        }

        return view('shop.checkout.index', [
            'summary' => $summary,
            'addresses' => $user->addresses,
            'defaultShipping' => $user->defaultShippingAddress(),
            'defaultBilling' => $user->defaultBillingAddress(),
            'codEnabled' => $this->settings->bool('payments', 'cod_enabled', true) && $summary['allow_cod'],
            'onlineEnabled' => $this->settings->bool('payments', 'online_enabled', true) && $summary['allow_online'],
            'cartCount' => $summary['item_count'],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'shipping_address_id' => ['required', 'exists:addresses,id'],
            'billing_address_id' => ['nullable', 'exists:addresses,id'],
            'billing_same_as_shipping' => ['sometimes', 'boolean'],
            'payment_method' => ['required', 'in:cod,paytm'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shipping_rate' => ['nullable', 'numeric', 'min:0'],
            'courier_name' => ['nullable', 'string', 'max:100'],
            'estimated_delivery_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $shippingAddress = Address::where('user_id', $user->id)->findOrFail($data['shipping_address_id']);
        $billingSame = $request->boolean('billing_same_as_shipping', true);
        $billingAddress = $billingSame
            ? $shippingAddress
            : Address::where('user_id', $user->id)->findOrFail($data['billing_address_id'] ?? 0);

        $codEnabled = $this->settings->bool('payments', 'cod_enabled', true);
        $onlineEnabled = $this->settings->bool('payments', 'online_enabled', true);

        if ($data['payment_method'] === 'cod' && ! $codEnabled) {
            return response()->json(['success' => false, 'message' => 'COD is disabled.'], 422);
        }
        if ($data['payment_method'] === 'paytm' && ! $onlineEnabled) {
            return response()->json(['success' => false, 'message' => 'Online payment is disabled.'], 422);
        }

        $cart = $this->cartService->getCart();
        if ($cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Your cart is empty.'], 422);
        }

        $pickup = (string) $this->settings->get('commerce', 'pickup_pincode', '110001');
        $weight = max($this->cartService->summary($cart)['weight'], 0.5);
        $quote = $this->shipping->driver()->serviceability(
            $pickup,
            $shippingAddress->postal_code,
            $weight,
            $data['payment_method'] === 'cod' ? 1 : 0
        );

        if (! ($quote['success'] ?? false)) {
            return response()->json(['success' => false, 'message' => $quote['message'] ?? 'Not serviceable.'], 422);
        }

        $shippingRate = (float) ($data['shipping_rate'] ?? data_get($quote, 'cheapest.rate', 0));
        $summary = $this->cartService->summary($cart, $shippingRate);

        if ($data['payment_method'] === 'cod' && ! $summary['allow_cod']) {
            return response()->json(['success' => false, 'message' => 'One or more items do not allow COD.'], 422);
        }
        if ($data['payment_method'] === 'paytm' && ! $summary['allow_online']) {
            return response()->json(['success' => false, 'message' => 'One or more items do not allow online payment.'], 422);
        }

        try {
            $order = DB::transaction(function () use ($data, $user, $cart, $summary, $shippingAddress, $billingAddress, $billingSame, $quote) {
                foreach ($cart->items as $item) {
                    $variant = ProductVariant::where('id', $item->product_variant_id)->lockForUpdate()->first();
                    if (! $variant || ! $variant->is_active || $variant->stock < $item->quantity) {
                        throw new \RuntimeException(($item->product->name ?? 'Product').' is out of stock.');
                    }
                }

                $order = Order::create([
                    'order_number' => 'ELP-'.strtoupper(Str::random(8)),
                    'user_id' => $user->id,
                    'customer_name' => $shippingAddress->name,
                    'customer_email' => $shippingAddress->email ?: $user->email,
                    'customer_phone' => $shippingAddress->phone,
                    'billing_same_as_shipping' => $billingSame,
                    'billing_name' => $billingAddress->name,
                    'billing_email' => $billingAddress->email ?: $user->email,
                    'billing_phone' => $billingAddress->phone,
                    'billing_address' => trim($billingAddress->address_line1.' '.($billingAddress->address_line2 ?? '')),
                    'billing_city' => $billingAddress->city,
                    'billing_state' => $billingAddress->state,
                    'billing_postal_code' => $billingAddress->postal_code,
                    'billing_country' => $billingAddress->country ?: 'IN',
                    'shipping_address' => trim($shippingAddress->address_line1.' '.($shippingAddress->address_line2 ?? '')),
                    'shipping_city' => $shippingAddress->city,
                    'shipping_state' => $shippingAddress->state,
                    'shipping_postal_code' => $shippingAddress->postal_code,
                    'shipping_country' => $shippingAddress->country ?: 'IN',
                    'subtotal' => $summary['subtotal'],
                    'shipping_amount' => $summary['shipping'],
                    'tax_amount' => $summary['tax'],
                    'tax_percent' => $summary['tax_percent'],
                    'total' => $summary['total'],
                    'currency' => 'INR',
                    'payment_method' => $data['payment_method'],
                    'payment_status' => $data['payment_method'] === 'cod' ? 'cod_pending' : 'pending',
                    'status' => $data['payment_method'] === 'cod' ? 'processing' : 'pending',
                    'notes' => $data['notes'] ?? null,
                    'estimated_delivery_days' => $data['estimated_delivery_days'] ?? data_get($quote, 'cheapest.etd'),
                    'courier_name' => $data['courier_name'] ?? data_get($quote, 'cheapest.courier_name'),
                    'shipping_weight' => $summary['weight'],
                ]);

                foreach ($cart->items as $item) {
                    $variant = ProductVariant::where('id', $item->product_variant_id)->lockForUpdate()->firstOrFail();

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $variant->id,
                        'product_name' => $item->product->name,
                        'product_sku' => $variant->sku,
                        'variant_label' => $variant->option_label,
                        'variant_options' => $variant->option_values,
                        'quantity' => $item->quantity,
                        'price' => $variant->price,
                        'total' => (float) $variant->price * $item->quantity,
                        'weight' => $variant->shippingWeight(),
                    ]);

                    $variant->decrement('stock', $item->quantity);
                }

                $this->cartService->clear();

                return $order->load('items');
            });

            $this->mailer->send('order_placed', $order->customer_email, [
                'customer_name' => $order->customer_name,
                'order_number' => $order->order_number,
                'total' => '₹'.number_format((float) $order->total, 2),
            ]);

            if ($data['payment_method'] === 'paytm') {
                $payment = $this->payments->paytm()->initiate($order);

                return response()->json([
                    'success' => true,
                    'message' => 'Redirecting to Paytm...',
                    'payment' => $payment,
                    'redirect' => $payment['redirect_url'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'redirect' => route('shop.checkout.success', $order->order_number),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function success(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->firstOrFail();

        return view('shop.checkout.success', [
            'order' => $order,
            'cartCount' => 0,
        ]);
    }
}
