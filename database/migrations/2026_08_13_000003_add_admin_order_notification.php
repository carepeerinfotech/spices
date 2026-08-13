<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Order notifications gain an admin copy: a settings row for the recipient
     * address, a toggle for the event, and the template itself. Existing rows are
     * left alone so a live store's SMTP configuration is never overwritten.
     */
    public function up(): void
    {
        $now = now();

        DB::table('settings')->insertOrIgnore([
            [
                'group' => 'email',
                'key' => 'admin_email',
                'value' => '',
                'type' => 'string',
                'is_encrypted' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'notifications',
                'key' => 'notify_order_placed_admin',
                'value' => '1',
                'type' => 'boolean',
                'is_encrypted' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('email_templates')->insertOrIgnore([
            'slug' => 'order_placed_admin',
            'name' => 'Order Placed (Admin Copy)',
            'subject' => 'New order {{order_number}} - {{total}}',
            'body' => '<p>New order <strong>{{order_number}}</strong> was placed.</p>'
                .'<p>Customer: {{customer_name}}<br>Email: {{customer_email}}<br>Phone: {{customer_phone}}</p>'
                .'<p>Payment: {{payment_method}} ({{payment_status}})<br>Total: {{total}}</p>'
                .'<p>Items:<br>{{items}}</p>',
            'placeholders' => json_encode([
                'order_number', 'customer_name', 'customer_email', 'customer_phone',
                'payment_method', 'payment_status', 'total', 'items',
            ]),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Cache::forget('settings.group.email');
        Cache::forget('settings.group.notifications');
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'email')->where('key', 'admin_email')->delete();
        DB::table('settings')->where('group', 'notifications')->where('key', 'notify_order_placed_admin')->delete();

        DB::table('email_templates')->where('slug', 'order_placed_admin')->delete();

        Cache::forget('settings.group.email');
        Cache::forget('settings.group.notifications');
    }
};
