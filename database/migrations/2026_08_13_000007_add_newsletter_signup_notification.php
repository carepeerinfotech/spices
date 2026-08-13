<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Newsletter signups notify the store owners, reusing the admin address added by
     * 2026_08_13_000003. Existing rows are left alone.
     */
    public function up(): void
    {
        $now = now();

        DB::table('settings')->insertOrIgnore([
            'group' => 'notifications',
            'key' => 'notify_newsletter_signup_admin',
            'value' => '1',
            'type' => 'boolean',
            'is_encrypted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('email_templates')->insertOrIgnore([
            'slug' => 'newsletter_signup_admin',
            'name' => 'Newsletter Signup (Admin)',
            'subject' => 'New newsletter subscriber',
            'body' => '<p><strong>{{email}}</strong> subscribed to the newsletter on {{subscribed_at}}.</p>'
                .'<p>That makes {{total_subscribers}} subscribers in total.</p>',
            'placeholders' => json_encode(['email', 'subscribed_at', 'total_subscribers']),
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
            ->where('key', 'notify_newsletter_signup_admin')
            ->delete();

        DB::table('email_templates')->where('slug', 'newsletter_signup_admin')->delete();

        Cache::forget('settings.group.notifications');
    }
};
