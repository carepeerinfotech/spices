<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Canonical bodies for the order email templates.
 *
 * Mirrors the order success page (resources/views/shop/checkout/success.blade.php):
 * confirmation tick, meta strip, items card with totals, address card, CTA buttons.
 *
 * Kept in code so the seeder and the branding migration cannot drift apart. These are
 * content fragments — the outer shell is resources/views/emails/layout.blade.php.
 */
class OrderEmailTemplates
{
    private const BRAND = '#b82125';

    private const INK = '#29272a';

    private const MUTED = '#746e69';

    private const LINE = '#eee9e2';

    private const CREAM = '#fff7ec';

    private const CARD = 'width:100%; background-color:#ffffff; border:1px solid #eee9e2; border-radius:16px; border-collapse:separate;';

    /** Opening line under "Order Confirmed!". */
    private const LEAD = 'Thank you, {{customer_name}}. We have received your order.';

    /** Superseded lead — kept so the migration can recognise and replace it. */
    private const LEAD_WITH_EMAIL = 'Thank you, {{customer_name}}. We have received your order and sent a confirmation to '
        .'<span style="color:#29272a; font-weight:600;">{{customer_email}}</span>.';

    /**
     * @return array<string, array{name: string, subject: string, body: string, placeholders: array<int, string>}>
     */
    public static function all(): array
    {
        $shared = [
            'customer_name', 'order_number', 'order_date',
            'payment_method', 'status_badge', 'total', 'items_rows', 'totals_rows',
            'shipping_address', 'delivery_note', 'shop_url', 'orders_url',
        ];

        return [
            'order_placed' => [
                'name' => 'Order Placed',
                'subject' => 'Order {{order_number}} confirmed',
                'body' => self::customerBody(),
                'placeholders' => $shared,
            ],
            'order_placed_admin' => [
                'name' => 'Order Placed (Admin Copy)',
                'subject' => 'New order {{order_number}} - {{total}}',
                'body' => self::adminBody(),
                'placeholders' => [...$shared, 'customer_email', 'customer_phone', 'payment_status'],
            ],
        ];
    }

    /**
     * Earlier revisions of each body, oldest first. A migration may replace a row
     * holding any of these; anything else means an admin edited the template in the
     * panel, so it is left alone.
     *
     * @return array<int, string>
     */
    public static function previousBodies(string $slug): array
    {
        return match ($slug) {
            'order_placed' => [
                '<p>Hi {{customer_name}},</p><p>Your order <strong>{{order_number}}</strong> totaling {{total}} has been placed.</p>',
                // First branded revision, which echoed the address back to the customer.
                self::customerBody(self::LEAD_WITH_EMAIL),
            ],
            'order_placed_admin' => [
                '<p>New order <strong>{{order_number}}</strong> was placed.</p>'
                    .'<p>Customer: {{customer_name}}<br>Email: {{customer_email}}<br>Phone: {{customer_phone}}</p>'
                    .'<p>Payment: {{payment_method}} ({{payment_status}})<br>Total: {{total}}</p>'
                    .'<p>Items:<br>{{items}}</p>',
            ],
            default => [],
        };
    }

    /**
     * Write the current bodies over any row still holding a shipped revision,
     * inserting the template when it is missing. Rows an admin has reworded in the
     * panel are left untouched. Safe to call repeatedly.
     */
    public static function syncUnmodified(): void
    {
        foreach (self::all() as $slug => $template) {
            $row = [
                'name' => $template['name'],
                'subject' => $template['subject'],
                'body' => $template['body'],
                'placeholders' => json_encode($template['placeholders']),
                'updated_at' => now(),
            ];

            $current = DB::table('email_templates')->where('slug', $slug)->first();

            if (! $current) {
                DB::table('email_templates')->insert($row + [
                    'slug' => $slug,
                    'is_active' => true,
                    'created_at' => now(),
                ]);

                continue;
            }

            if (in_array(trim($current->body), self::previousBodies($slug), true)) {
                DB::table('email_templates')->where('slug', $slug)->update($row);
            }
        }
    }

    private static function customerBody(?string $lead = null): string
    {
        return self::confirmationHeader($lead ?? self::LEAD)
            .self::metaStrip()
            .self::spacer(20)
            .self::itemsCard('Order Items')
            .self::spacer(20)
            .self::addressCard('Shipping Address', true)
            .self::spacer(28)
            .self::buttons();
    }

    private static function adminBody(): string
    {
        return '<h1 style="margin:0 0 6px 0; font-size:26px; font-weight:700; color:'.self::INK.'; text-align:center;">New Order Received</h1>'
            .'<p style="margin:0 0 24px 0; font-size:14px; line-height:1.6; color:'.self::MUTED.'; text-align:center;">'
            .'{{customer_name}} just placed an order. Payment is <strong style="color:'.self::INK.';">{{payment_status}}</strong>.</p>'

            .self::metaStrip()
            .self::spacer(20)
            .self::itemsCard('Order Items')
            .self::spacer(20)

            .self::card('Customer',
                '<p style="margin:0; font-size:14px; line-height:1.8; color:'.self::MUTED.';">'
                .'{{customer_name}}<br>{{customer_email}}<br>{{customer_phone}}</p>'
            )
            .self::spacer(20)
            .self::addressCard('Ship To', false);
    }

    /**
     * Green tick + "Order Confirmed!", as on the success page.
     */
    private static function confirmationHeader(string $lead): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
            .'<tr><td align="center" style="padding-bottom:16px;">'
            .'<table role="presentation" cellpadding="0" cellspacing="0" border="0">'
            .'<tr><td width="64" height="64" align="center" valign="middle" '
            .'style="width:64px; height:64px; background-color:#d1fae5; border-radius:32px; '
            .'font-size:30px; line-height:64px; color:#059669; font-weight:700;">&#10003;</td></tr>'
            .'</table>'
            .'</td></tr>'
            .'<tr><td align="center">'
            .'<h1 style="margin:0 0 8px 0; font-size:30px; font-weight:700; color:'.self::INK.';">Order Confirmed!</h1>'
            .'<p style="margin:0 0 24px 0; font-size:14px; line-height:1.6; color:'.self::MUTED.';">'
            .$lead
            .'</p>'
            .'</td></tr>'
            .'</table>';
    }

    /**
     * Order number / date / payment / status, the four-up strip from the success page.
     */
    private static function metaStrip(): string
    {
        $label = 'margin:0 0 4px 0; font-size:10px; letter-spacing:0.06em; text-transform:uppercase; color:#a8a29e;';
        $value = 'margin:0; font-size:13px; font-weight:600; color:'.self::INK.';';

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="'.self::CARD.'">'
            .'<tr>'
            .'<td width="28%" valign="top" style="padding:18px 12px 18px 20px;">'
            .'<p style="'.$label.'">Order Number</p>'
            .'<p style="margin:0; font-size:13px; font-weight:700; color:'.self::BRAND.';">{{order_number}}</p>'
            .'</td>'
            .'<td width="20%" valign="top" style="padding:18px 12px;">'
            .'<p style="'.$label.'">Date</p><p style="'.$value.'">{{order_date}}</p>'
            .'</td>'
            .'<td width="28%" valign="top" style="padding:18px 12px;">'
            .'<p style="'.$label.'">Payment</p><p style="'.$value.'">{{payment_method}}</p>'
            .'</td>'
            .'<td width="24%" valign="top" style="padding:18px 20px 18px 12px;">'
            .'<p style="'.$label.'">Status</p>{{status_badge}}'
            .'</td>'
            .'</tr>'
            .'</table>';
    }

    /**
     * Items card: titled header, one row per item, cream totals strip beneath.
     */
    private static function itemsCard(string $title): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="'.self::CARD.' overflow:hidden;">'
            .'<tr><td style="padding:16px 24px; border-bottom:1px solid '.self::LINE.'; '
            .'font-size:15px; font-weight:600; color:'.self::INK.';">'.e($title).'</td></tr>'
            .'<tr><td style="padding:0;">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; border-collapse:collapse;">'
            .'{{items_rows}}'
            .'</table>'
            .'</td></tr>'
            .'<tr><td style="padding:16px 24px; background-color:'.self::CREAM.'; border-top:1px solid '.self::LINE.';">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; border-collapse:collapse;">'
            .'{{totals_rows}}'
            .'</table>'
            .'</td></tr>'
            .'</table>';
    }

    private static function addressCard(string $title, bool $withDelivery): string
    {
        return self::card($title,
            '<p style="margin:0; font-size:14px; line-height:1.8; color:'.self::MUTED.';">{{shipping_address}}</p>'
            .($withDelivery ? '{{delivery_note}}' : '')
        );
    }

    private static function card(string $title, string $inner): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="'.self::CARD.'">'
            .'<tr><td style="padding:20px 24px;">'
            .'<p style="margin:0 0 10px 0; font-size:15px; font-weight:600; color:'.self::INK.';">'.e($title).'</p>'
            .$inner
            .'</td></tr>'
            .'</table>';
    }

    /**
     * Brand pill + outlined pill, matching the success page CTAs.
     */
    private static function buttons(): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
            .'<tr><td align="center">'
            .'<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>'
            .'<td style="padding:0 6px;">'
            .'<a href="{{shop_url}}" style="display:inline-block; padding:13px 26px; border-radius:999px; '
            .'background-color:'.self::BRAND.'; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none;">Continue Shopping</a>'
            .'</td>'
            .'<td style="padding:0 6px;">'
            .'<a href="{{orders_url}}" style="display:inline-block; padding:12px 26px; border-radius:999px; '
            .'border:1px solid '.self::LINE.'; color:'.self::INK.'; font-size:13px; font-weight:700; text-decoration:none;">View My Orders</a>'
            .'</td>'
            .'</tr></table>'
            .'</td></tr>'
            .'</table>';
    }

    private static function spacer(int $height): string
    {
        return '<div style="height:'.$height.'px; line-height:'.$height.'px; font-size:0;">&nbsp;</div>';
    }
}
