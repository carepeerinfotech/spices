<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hide the courier name and delivery estimate from customers on the order success
     * page and in the confirmation email. Defaults off because the Shiprocket fake
     * driver reports placeholder couriers ("Fake Express") that must not reach buyers.
     * Flip the toggle under Settings > Shipping once live rates are in place.
     */
    public function up(): void
    {
        $now = now();

        DB::table('settings')->insertOrIgnore([
            'group' => 'shipping',
            'key' => 'show_delivery_details',
            'value' => '0',
            'type' => 'boolean',
            'is_encrypted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Cache::forget('settings.group.shipping');
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'shipping')
            ->where('key', 'show_delivery_details')
            ->delete();

        Cache::forget('settings.group.shipping');
    }
};
