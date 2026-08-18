<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Checkout no longer requires an account up front — it creates one from the
     * billing details instead. This is the mail that tells the customer the
     * account exists and lets them pick a password for it.
     */
    public function up(): void
    {
        $now = now();

        DB::table('settings')->insertOrIgnore([
            'group' => 'notifications',
            'key' => 'notify_account_created',
            'value' => '1',
            'type' => 'boolean',
            'is_encrypted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('email_templates')->insertOrIgnore([
            'slug' => 'account_created',
            'name' => 'Account Created (Checkout)',
            'subject' => 'Your account is ready',
            'body' => '<p>Hi {{customer_name}},</p>'
                .'<p>We created an account for <strong>{{email}}</strong> while placing your order, so you can track it and check out faster next time.</p>'
                .'<p><a href="{{set_password_url}}">Set your password</a> to finish setting it up.</p>'
                .'<p>You can view your orders any time at <a href="{{account_url}}">{{account_url}}</a>.</p>',
            'placeholders' => json_encode(['customer_name', 'email', 'set_password_url', 'account_url']),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Cache::forget('settings.group.notifications');
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'notifications')
            ->where('key', 'notify_account_created')
            ->delete();

        DB::table('email_templates')->where('slug', 'account_created')->delete();

        Cache::forget('settings.group.notifications');
    }
};
