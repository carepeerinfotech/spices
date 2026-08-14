<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOffer;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\Mail\OrderMailData;
use App\Services\Mail\TemplateMailer;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Settings\SettingsService;
use App\Services\Shipping\ShippingManager;
use App\Support\Features;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /** Order numbers placed in this session, viewable without signing in. */
    private const PLACED_ORDERS_KEY = 'checkout.placed_orders';

    public function __construct(
        private CartService $cartService,
        private SettingsService $settings,
        private ShippingManager $shipping,
        private PaymentGatewayManager $payments,
        private TemplateMailer $mailer,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $cart = $this->cartService->getCart();
        $summary = $this->cartService->summary($cart);

        if ($summary['item_count'] < 1) {
            return redirect()->route('shop.cart')->with('error', 'Your cart is empty.');
        }

        // A returning customer who uses the sign-in link on this page should land
        // back here, cart intact, rather than on the account dashboard.
        if (! $user) {
            $request->session()->put('url.intended', route('shop.checkout'));
        }

        $address = $user?->defaultShippingAddress() ?: $user?->defaultBillingAddress();

        return view('shop.checkout.index', [
            'summary' => $summary,
            'user' => $user,
            'address' => $address,
            'codEnabled' => $this->settings->bool('payments', 'cod_enabled', true) && $summary['allow_cod'],
            'onlineEnabled' => $this->settings->bool('payments', 'online_enabled', true) && $summary['allow_online'],
            'cartCount' => $summary['item_count'],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'billing_name' => ['required', 'string', 'max:255'],
            'billing_email' => ['required', 'email', 'max:255'],
            'billing_phone' => ['required', 'string', 'max:20'],
            'billing_address_line1' => ['required', 'string', 'max:255'],
            'billing_address_line2' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['required', 'string', 'max:100'],
            'billing_state' => ['required', 'string', 'max:100'],
            'billing_postal_code' => ['required', 'regex:/^\d{6}$/'],
            'billing_country' => ['required', 'in:'.implode(',', array_keys(config('countries')))],

            'ship_to_different_address' => ['sometimes', 'boolean'],
            'shipping_name' => ['required_if:ship_to_different_address,1', 'nullable', 'string', 'max:255'],
            'shipping_address_line1' => ['required_if:ship_to_different_address,1', 'nullable', 'string', 'max:255'],
            'shipping_address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required_if:ship_to_different_address,1', 'nullable', 'string', 'max:100'],
            'shipping_state' => ['required_if:ship_to_different_address,1', 'nullable', 'string', 'max:100'],
            'shipping_postal_code' => ['required_if:ship_to_different_address,1', 'nullable', 'regex:/^\d{6}$/'],
            'shipping_country' => ['nullable', 'in:'.implode(',', array_keys(config('countries')))],

            'payment_method' => ['required', 'in:cod,paytm'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shipping_rate' => ['nullable', 'numeric', 'min:0'],
            'courier_name' => ['nullable', 'string', 'max:100'],
            'estimated_delivery_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $shipToDifferent = $request->boolean('ship_to_different_address');

        $billingAddressLine = trim($data['billing_address_line1'].' '.($data['billing_address_line2'] ?? ''));

        if ($shipToDifferent) {
            $shippingName = $data['shipping_name'];
            $shippingAddressLine = trim($data['shipping_address_line1'].' '.($data['shipping_address_line2'] ?? ''));
            $shippingCity = $data['shipping_city'];
            $shippingState = $data['shipping_state'];
            $shippingPostal = $data['shipping_postal_code'];
            $shippingCountry = $data['shipping_country'] ?? $data['billing_country'];
        } else {
            $shippingName = $data['billing_name'];
            $shippingAddressLine = $billingAddressLine;
            $shippingCity = $data['billing_city'];
            $shippingState = $data['billing_state'];
            $shippingPostal = $data['billing_postal_code'];
            $shippingCountry = $data['billing_country'];
        }

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
            $shippingPostal,
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

        // Every order belongs to a customer record, but nobody has to log in to
        // get one: an unknown email gets a fresh account created below, and a
        // known one files the order under its owner without signing anyone in.
        $account = $user ?: User::where('email', $data['billing_email'])->first();
        $createsAccount = $account === null;

        try {
            $order = DB::transaction(function () use (
                $data, $user, &$account, $createsAccount, $cart, $summary, $quote, $shipToDifferent,
                $billingAddressLine, $shippingName, $shippingAddressLine, $shippingCity, $shippingState, $shippingPostal, $shippingCountry
            ) {
                foreach ($cart->items as $item) {
                    $variant = ProductVariant::where('id', $item->product_variant_id)->lockForUpdate()->first();
                    if (! $variant || ! $variant->is_active || $variant->stock < $item->quantity) {
                        throw new \RuntimeException(($item->product->name ?? 'Product').' is out of stock.');
                    }
                }

                // Rolled back with the order, so a failed checkout leaves no
                // half-registered customer behind. The password is a throwaway;
                // the welcome mail carries a link to set a real one.
                if ($createsAccount) {
                    $account = User::create([
                        'name' => $data['billing_name'],
                        'email' => $data['billing_email'],
                        'phone' => $data['billing_phone'],
                        'password' => Str::password(32),
                        'is_active' => true,
                        'is_customer' => true,
                    ]);
                }

                // Only touch the address book when the session owns the account.
                // A guest typing a registered customer's email must not be able
                // to overwrite that customer's saved address.
                if ($user || $createsAccount) {
                    Address::updateOrCreate(
                        ['user_id' => $account->id, 'is_default_shipping' => true],
                        [
                            'label' => 'Home',
                            'name' => $data['billing_name'],
                            'phone' => $data['billing_phone'],
                            'email' => $data['billing_email'],
                            'address_line1' => $data['billing_address_line1'],
                            'address_line2' => $data['billing_address_line2'] ?? null,
                            'city' => $data['billing_city'],
                            'state' => $data['billing_state'],
                            'postal_code' => $data['billing_postal_code'],
                            'country' => $data['billing_country'],
                            'is_default_shipping' => true,
                            'is_default_billing' => true,
                        ]
                    );
                }

                $order = Order::create([
                    'order_number' => 'ELP-'.strtoupper(Str::random(8)),
                    'user_id' => $account->id,
                    'customer_name' => $data['billing_name'],
                    'customer_email' => $data['billing_email'],
                    'customer_phone' => $data['billing_phone'],
                    'billing_same_as_shipping' => ! $shipToDifferent,
                    'billing_name' => $data['billing_name'],
                    'billing_email' => $data['billing_email'],
                    'billing_phone' => $data['billing_phone'],
                    'billing_address' => $billingAddressLine,
                    'billing_city' => $data['billing_city'],
                    'billing_state' => $data['billing_state'],
                    'billing_postal_code' => $data['billing_postal_code'],
                    'billing_country' => $data['billing_country'],
                    'shipping_name' => $shippingName,
                    'shipping_address' => $shippingAddressLine,
                    'shipping_city' => $shippingCity,
                    'shipping_state' => $shippingState,
                    'shipping_postal_code' => $shippingPostal,
                    'shipping_country' => $shippingCountry,
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

                    // Price the line the same way the cart summary did.
                    $offer = $item->product?->activeOffer();
                    $unitDiscount = $offer?->discountFor((float) $variant->price) ?? 0.0;
                    $unitPrice = round((float) $variant->price - $unitDiscount, 2);

                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $variant->id,
                        'product_name' => $item->product->name,
                        'product_sku' => $variant->sku,
                        'variant_label' => $variant->option_label,
                        'variant_options' => $variant->option_values,
                        'quantity' => $item->quantity,
                        'price' => $unitPrice,
                        'total' => round($unitPrice * $item->quantity, 2),
                        'weight' => $variant->shippingWeight(),
                    ]);

                    // Snapshot the offer that produced the discount just charged.
                    if ($offer) {
                        OrderOffer::captureFor($orderItem, $offer, $unitDiscount);
                    }

                    $variant->decrement('stock', $item->quantity);
                }

                $this->cartService->clear();

                return $order->load('items', 'offers');
            });

            $this->rememberPlacedOrder($request, $order);

            // Sign in only an account this checkout just created. An order placed
            // against an existing customer's email says nothing about who is at
            // the keyboard, so that case stays a guest session.
            if ($createsAccount) {
                Auth::login($account);
                $request->session()->regenerate();
            }

            $this->sendOrderNotifications($order);

            if ($createsAccount) {
                $this->sendAccountCreatedMail($account);
            }

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
        $order = Order::where('order_number', $orderNumber)->with('items')->firstOrFail();

        // 404 rather than 403: an order number should not be confirmable by
        // anyone who merely guesses it.
        abort_unless($this->canViewOrder($request, $order), 404);

        return view('shop.checkout.success', [
            'order' => $order,
            'cartCount' => 0,
            'showDeliveryDetails' => $this->settings->bool('shipping', 'show_delivery_details', false),
        ]);
    }

    /**
     * Its owner may always see an order; so may whoever placed it in this
     * session, which is what keeps the confirmation page reachable for a guest
     * whose order went to an account they were not signed into.
     */
    private function canViewOrder(Request $request, Order $order): bool
    {
        $user = $request->user();

        if ($user && $order->user_id === $user->id) {
            return true;
        }

        return in_array($order->order_number, $this->placedOrders($request), true);
    }

    /**
     * @return array<int, string>
     */
    private function placedOrders(Request $request): array
    {
        return array_values((array) $request->session()->get(self::PLACED_ORDERS_KEY, []));
    }

    private function rememberPlacedOrder(Request $request, Order $order): void
    {
        // The order is placed, so the "come back to checkout after signing in"
        // intent recorded by index() has been served.
        $request->session()->forget('url.intended');

        $placed = array_unique([...$this->placedOrders($request), $order->order_number]);

        // Bounded so a long-lived session cannot grow the cookie payload forever.
        $request->session()->put(self::PLACED_ORDERS_KEY, array_slice(array_values($placed), -20));
    }

    /**
     * Welcome a customer whose account the checkout form just created. The
     * generated password is throwaway, so the mail carries a reset link for
     * choosing a real one; with password reset switched off there is no link to
     * send and the mail is skipped.
     */
    private function sendAccountCreatedMail(User $user): void
    {
        if (! Features::passwordReset()) {
            return;
        }

        try {
            /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
            $broker = Password::broker();
            $token = $broker->createToken($user);

            $this->mailer->send('account_created', $user->email, [
                'customer_name' => $user->name,
                'email' => $user->email,
                'set_password_url' => route('password.reset', ['token' => $token, 'email' => $user->email]),
                'account_url' => route('account.dashboard'),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Notify the customer, then send the store owners their own copy.
     * Mail failures must never roll back a paid-for order.
     */
    private function sendOrderNotifications(Order $order): void
    {
        try {
            $this->mailer->send('order_placed', $order->customer_email, OrderMailData::customer($order));
        } catch (\Throwable $e) {
            report($e);
        }

        $admins = $this->mailer->adminRecipients();
        if ($admins === []) {
            return;
        }

        try {
            $this->mailer->send('order_placed_admin', $admins, OrderMailData::admin($order));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
