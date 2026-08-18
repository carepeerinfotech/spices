<?php

namespace App\Services\Mail;

use App\Models\Order;
use App\Services\Settings\SettingsService;

/**
 * Placeholder values for the order emails.
 *
 * The markup mirrors resources/views/shop/checkout/success.blade.php, rebuilt as
 * tables with inline styles because Outlook drops <style> blocks and flex/grid.
 * Colours are the storefront tokens from public/css/app.css.
 */
class OrderMailData
{
    private const BRAND = '#b82125';

    private const INK = '#29272a';

    private const MUTED = '#746e69';

    private const LINE = '#eee9e2';

    private const CREAM = '#fff7ec';

    /** Matches the $statusColors map on the success page. */
    private const STATUS_COLORS = [
        'pending' => ['#fef3c7', '#b45309'],
        'processing' => ['#e0f2fe', '#0369a1'],
        'shipped' => ['#e0e7ff', '#4338ca'],
        'delivered' => ['#d1fae5', '#047857'],
        'cancelled' => ['#ffe4e6', '#be123c'],
    ];

    public static function money(float $amount): string
    {
        return '₹'.number_format($amount, 2);
    }

    /**
     * @return array<string, string>
     */
    public static function customer(Order $order): array
    {
        return [
            'customer_name' => e($order->customer_name),
            'customer_email' => e($order->customer_email),
            'order_number' => e($order->order_number),
            'order_date' => $order->created_at?->format('M j, Y') ?? '',
            'payment_method' => self::paymentLabel($order),
            'status_badge' => self::statusBadge($order),
            'total' => self::money((float) $order->total),
            'items_rows' => self::itemsRows($order),
            'totals_rows' => self::totalsRows($order),
            'shipping_address' => self::shippingAddress($order),
            'delivery_note' => self::deliveryNote($order),
            'shop_url' => e(route('shop.catalog')),
            'orders_url' => e(route('account.dashboard')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function admin(Order $order): array
    {
        return self::customer($order) + [
            'customer_phone' => e($order->customer_phone ?: 'Not provided'),
            'payment_status' => e(ucfirst((string) $order->payment_status)),
        ];
    }

    private static function paymentLabel(Order $order): string
    {
        return $order->payment_method === 'cod' ? 'Cash on Delivery' : 'Paytm (Online)';
    }

    private static function statusBadge(Order $order): string
    {
        $status = (string) $order->status;
        [$background, $color] = self::STATUS_COLORS[$status] ?? ['#f5f5f4', '#57534e'];

        return '<span style="display:inline-block; padding:4px 11px; border-radius:999px; '
            .'background-color:'.$background.'; color:'.$color.'; '
            .'font-size:11px; font-weight:700; text-transform:capitalize;">'.e($status).'</span>';
    }

    private static function deliveryNote(Order $order): string
    {
        if (! app(SettingsService::class)->bool('shipping', 'show_delivery_details', false)) {
            return '';
        }

        $lines = [];

        if ($order->courier_name) {
            $lines[] = 'Courier: <span style="color:'.self::INK.'; font-weight:600;">'.e($order->courier_name).'</span>';
        }

        if ($days = (int) $order->estimated_delivery_days) {
            $lines[] = 'Estimated delivery: <span style="color:'.self::INK.'; font-weight:600;">'
                .$days.' '.($days === 1 ? 'day' : 'days').'</span>';
        }

        if ($lines === []) {
            return '';
        }

        return '<div style="margin-top:16px; padding-top:16px; border-top:1px solid '.self::LINE.'; '
            .'font-size:13px; line-height:1.8; color:'.self::MUTED.';">'.implode('<br>', $lines).'</div>';
    }

    /**
     * Item rows for the "Order Items" card — name, variant, qty x price, line total.
     */
    private static function itemsRows(Order $order): string
    {
        $rows = '';
        $last = $order->items->count() - 1;

        foreach ($order->items->values() as $index => $item) {
            $border = $index === $last ? 'none' : '1px solid '.self::LINE;

            $rows .= '<tr>'
                .'<td style="padding:14px 24px; border-bottom:'.$border.';">'
                .'<div style="font-size:14px; font-weight:600; color:'.self::INK.';">'.e($item->product_name).'</div>'
                .($item->variant_label
                    ? '<div style="margin-top:2px; font-size:12px; color:'.self::MUTED.';">'.e($item->variant_label).'</div>'
                    : '')
                .'<div style="margin-top:2px; font-size:12px; color:#a8a29e;">Qty '.$item->quantity
                .' &times; '.self::money((float) $item->price).'</div>'
                .'</td>'
                .'<td align="right" valign="top" style="padding:14px 24px; border-bottom:'.$border.'; '
                .'font-size:14px; font-weight:600; color:'.self::INK.'; white-space:nowrap;">'
                .self::money((float) $item->total)
                .'</td>'
                .'</tr>';
        }

        return $rows;
    }

    /**
     * Subtotal / shipping / GST / total rows for the cream strip under the items.
     */
    private static function totalsRows(Order $order): string
    {
        $discount = $order->offersDiscount();

        $rows = self::totalsRow('Subtotal', self::money((float) $order->subtotal));

        if ($discount > 0) {
            $rows .= self::totalsRow('Discount', '-'.self::money($discount));
        }

        $shipping = (float) $order->shipping_amount;
        $rows .= self::totalsRow('Shipping', $shipping > 0 ? self::money($shipping) : 'Free');

        if ((float) $order->tax_amount > 0) {
            $percent = rtrim(rtrim((string) $order->tax_percent, '0'), '.');
            $rows .= self::totalsRow('GST'.($percent !== '' ? ' ('.$percent.'%)' : ''), self::money((float) $order->tax_amount));
        }

        return $rows
            .'<tr>'
            .'<td style="padding:12px 0 0 0; border-top:1px solid '.self::LINE.'; '
            .'font-size:15px; font-weight:700; color:'.self::INK.';">Total</td>'
            .'<td align="right" style="padding:12px 0 0 0; border-top:1px solid '.self::LINE.'; '
            .'font-size:15px; font-weight:700; color:'.self::BRAND.'; white-space:nowrap;">'
            .self::money((float) $order->total)
            .'</td>'
            .'</tr>';
    }

    private static function totalsRow(string $label, string $value): string
    {
        return '<tr>'
            .'<td style="padding:4px 0; font-size:13px; color:'.self::MUTED.';">'.e($label).'</td>'
            .'<td align="right" style="padding:4px 0; font-size:13px; color:'.self::INK.'; white-space:nowrap;">'.e($value).'</td>'
            .'</tr>';
    }

    private static function shippingAddress(Order $order): string
    {
        $lines = array_filter([
            $order->shipping_name ?: $order->customer_name,
            $order->shipping_address,
            trim(implode(', ', array_filter([$order->shipping_city, $order->shipping_state]))
                .' '.$order->shipping_postal_code),
            $order->shipping_country ?: 'India',
        ], fn ($line) => filled(trim((string) $line)));

        return implode('<br>', array_map(fn ($line) => e(trim((string) $line)), $lines));
    }

    /**
     * Shared chrome so the templates can wrap content in a success-page card.
     */
    public static function cream(): string
    {
        return self::CREAM;
    }
}
