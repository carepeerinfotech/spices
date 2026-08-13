<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Contact enquiries notify the store owners, reusing the admin address added by
     * 2026_08_13_000003. Existing rows are left alone.
     */
    public function up(): void
    {
        $now = now();

        DB::table('settings')->insertOrIgnore([
            'group' => 'notifications',
            'key' => 'notify_contact_message_admin',
            'value' => '1',
            'type' => 'boolean',
            'is_encrypted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('email_templates')->insertOrIgnore([
            'slug' => 'contact_message_admin',
            'name' => 'Contact Enquiry (Admin)',
            'subject' => 'New enquiry from {{name}}',
            'body' => '<p>A new enquiry arrived on {{received_at}}.</p>'
                .'<p>Name: {{name}}<br>Email: {{email}}<br>Phone: {{phone}}</p>'
                .'<p>Message:<br>{{message}}</p>',
            'placeholders' => json_encode(['name', 'email', 'phone', 'message', 'received_at']),
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
            ->where('key', 'notify_contact_message_admin')
            ->delete();

        DB::table('email_templates')->where('slug', 'contact_message_admin')->delete();

        Cache::forget('settings.group.notifications');
    }
};
