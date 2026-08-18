<?php

namespace Tests\Feature\Shop;

use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Mail\OrderMailData;
use App\Services\Mail\TemplateMailer;
use App\Services\Settings\SettingsService;
use App\Support\OrderEmailTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderEmailBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(SettingsService::class);
        $settings->set('notifications', 'enabled', true, 'boolean');
        $settings->set('commerce', 'store_name', 'Elephant Shop');
        $settings->set('commerce', 'support_email', 'hello@elephantshop.test');

        foreach (OrderEmailTemplates::all() as $slug => $template) {
            EmailTemplate::updateOrCreate(['slug' => $slug], [
                'name' => $template['name'],
                'subject' => $template['subject'],
                'body' => $template['body'],
                'placeholders' => $template['placeholders'],
                'is_active' => true,
            ]);
        }
    }

    private function order(): Order
    {
        $order = Order::create([
            'order_number' => 'ELP-TEST01',
            'customer_name' => 'Amrit',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '9876543210',
            'shipping_name' => 'Amrit',
            'shipping_address' => '12 Connaught Place',
            'shipping_city' => 'New Delhi',
            'shipping_state' => 'Delhi',
            'shipping_postal_code' => '110001',
            'shipping_country' => 'India',
            'subtotal' => 500,
            'shipping_amount' => 49,
            'tax_amount' => 90,
            'tax_percent' => 18,
            'total' => 639,
            'currency' => 'INR',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'estimated_delivery_days' => 4,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Garam Masala',
            'product_sku' => 'GM-100',
            'variant_label' => '100 g',
            'quantity' => 2,
            'price' => 250,
            'total' => 500,
        ]);

        return $order->load('items', 'offers');
    }

    private function renderCustomerMail(): string
    {
        app(TemplateMailer::class)->send('order_placed', 'buyer@example.com', OrderMailData::customer($this->order()));

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages()->all();

        return (string) array_values($messages)[0]->getOriginalMessage()->getHtmlBody();
    }

    public function test_the_mail_carries_the_storefront_branding(): void
    {
        $body = $this->renderCustomerMail();

        // Brand red, cream ground and the dark footer band from public/css/app.css.
        $this->assertStringContainsString('#b82125', $body);
        $this->assertStringContainsString('#fff7ec', $body);
        $this->assertStringContainsString('#1a1714', $body);

        $this->assertStringContainsString('assets/images/logo.png', $body);
        $this->assertStringContainsString('Elephant Shop', $body);
        $this->assertStringContainsString('hello@elephantshop.test', $body);
    }

    public function test_the_confirmation_does_not_echo_the_address_back_to_the_customer(): void
    {
        $body = $this->renderCustomerMail();

        $this->assertStringContainsString('Thank you, Amrit. We have received your order.', $body);
        $this->assertStringNotContainsString('sent a confirmation to', $body);
        $this->assertStringNotContainsString('buyer@example.com', $body);
    }

    public function test_the_admin_copy_still_shows_the_customer_address(): void
    {
        app(TemplateMailer::class)->send('order_placed_admin', 'owner@shop.test', OrderMailData::admin($this->order()));

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages()->all();
        $body = (string) array_values($messages)[0]->getOriginalMessage()->getHtmlBody();

        $this->assertStringContainsString('buyer@example.com', $body);
        $this->assertStringContainsString('9876543210', $body);
    }

    public function test_the_mail_mirrors_the_success_page_sections(): void
    {
        $body = $this->renderCustomerMail();

        $this->assertStringContainsString('Order Confirmed!', $body);
        $this->assertStringContainsString('Order Items', $body);
        $this->assertStringContainsString('Shipping Address', $body);
        $this->assertStringContainsString('Continue Shopping', $body);
        $this->assertStringContainsString('View My Orders', $body);
    }

    public function test_the_order_details_are_rendered_into_the_tables(): void
    {
        $body = $this->renderCustomerMail();

        $this->assertStringContainsString('ELP-TEST01', $body);
        $this->assertStringContainsString('Garam Masala', $body);
        $this->assertStringContainsString('100 g', $body);
        $this->assertStringContainsString('Qty 2', $body);
        $this->assertStringContainsString('Cash on Delivery', $body);

        // Subtotal, shipping, GST line and grand total.
        $this->assertStringContainsString('₹500.00', $body);
        $this->assertStringContainsString('₹49.00', $body);
        $this->assertStringContainsString('GST (18%)', $body);
        $this->assertStringContainsString('₹639.00', $body);

        $this->assertStringContainsString('12 Connaught Place', $body);
        $this->assertStringContainsString('New Delhi, Delhi 110001', $body);
    }

    public function test_courier_and_delivery_estimate_are_hidden_by_default(): void
    {
        // The Shiprocket fake driver reports placeholder couriers, so this stays off
        // until live rates are configured.
        $body = $this->renderCustomerMail();

        $this->assertStringNotContainsString('Estimated delivery', $body);
        $this->assertStringNotContainsString('Courier', $body);
    }

    public function test_enabling_the_setting_shows_courier_and_delivery_estimate(): void
    {
        app(SettingsService::class)->set('shipping', 'show_delivery_details', true, 'boolean');

        $order = $this->order();
        $order->update(['courier_name' => 'Blue Dart']);

        $note = OrderMailData::customer($order->fresh('items'))['delivery_note'];

        $this->assertStringContainsString('Blue Dart', $note);
        $this->assertStringContainsString('4 days', $note);
    }

    public function test_no_placeholder_is_left_unreplaced(): void
    {
        $this->assertSame(0, preg_match('/\{\{\s*[a-z_]+\s*\}\}/i', $this->renderCustomerMail()));
    }

    public function test_free_shipping_is_worded_rather_than_shown_as_zero(): void
    {
        $order = $this->order();
        $order->update(['shipping_amount' => 0]);

        $this->assertStringContainsString('Free', OrderMailData::customer($order->fresh('items'))['totals_rows']);
    }

    public function test_the_status_pill_uses_the_success_page_colours(): void
    {
        $order = $this->order();

        $order->update(['status' => 'delivered']);
        $this->assertStringContainsString('#d1fae5', OrderMailData::customer($order->fresh('items'))['status_badge']);

        $order->update(['status' => 'cancelled']);
        $this->assertStringContainsString('#ffe4e6', OrderMailData::customer($order->fresh('items'))['status_badge']);
    }

    public function test_product_names_are_escaped(): void
    {
        $order = $this->order();
        $order->items()->first()->update(['product_name' => 'Chilli <script>alert(1)</script>']);

        $rows = OrderMailData::customer($order->fresh('items'))['items_rows'];

        $this->assertStringNotContainsString('<script>', $rows);
        $this->assertStringContainsString('&lt;script&gt;', $rows);
    }
}
