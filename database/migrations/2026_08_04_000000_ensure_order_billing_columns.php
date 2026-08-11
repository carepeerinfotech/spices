<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Re-adds the `orders` columns introduced by
     * 2026_07_18_100001_expand_catalog_for_variants_and_media, for databases
     * where that migration is recorded as run but the columns are absent
     * (e.g. restored from a dump predating it). Adds only what is missing, so
     * it is a no-op on databases that are already correct.
     */
    public function up(): void
    {
        $existing = Schema::getColumnListing('orders');

        // Declaration order matters: `after()` anchors may themselves be
        // added by this same ALTER statement.
        $columns = [
            'currency' => fn (Blueprint $t) => $t->string('currency', 3)->default('INR')->after('total'),
            'payment_method' => fn (Blueprint $t) => $t->string('payment_method')->default('cod')->after('currency'),
            'payment_status' => fn (Blueprint $t) => $t->string('payment_status')->default('pending')->after('payment_method'),
            'tax_percent' => fn (Blueprint $t) => $t->decimal('tax_percent', 5, 2)->default(0)->after('tax_amount'),
            'billing_name' => fn (Blueprint $t) => $t->string('billing_name')->nullable()->after('customer_phone'),
            'billing_email' => fn (Blueprint $t) => $t->string('billing_email')->nullable()->after('billing_name'),
            'billing_phone' => fn (Blueprint $t) => $t->string('billing_phone')->nullable()->after('billing_email'),
            'billing_address' => fn (Blueprint $t) => $t->text('billing_address')->nullable()->after('billing_phone'),
            'billing_city' => fn (Blueprint $t) => $t->string('billing_city')->nullable()->after('billing_address'),
            'billing_state' => fn (Blueprint $t) => $t->string('billing_state')->nullable()->after('billing_city'),
            'billing_postal_code' => fn (Blueprint $t) => $t->string('billing_postal_code')->nullable()->after('billing_state'),
            'billing_country' => fn (Blueprint $t) => $t->string('billing_country')->default('IN')->after('billing_postal_code'),
            'billing_same_as_shipping' => fn (Blueprint $t) => $t->boolean('billing_same_as_shipping')->default(true)->after('billing_country'),
            'estimated_delivery_days' => fn (Blueprint $t) => $t->unsignedInteger('estimated_delivery_days')->nullable()->after('notes'),
            'courier_name' => fn (Blueprint $t) => $t->string('courier_name')->nullable()->after('estimated_delivery_days'),
            'shipping_weight' => fn (Blueprint $t) => $t->decimal('shipping_weight', 8, 3)->nullable()->after('courier_name'),
        ];

        $missing = array_diff_key($columns, array_flip($existing));

        if ($missing === []) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) use ($missing) {
            foreach ($missing as $definition) {
                $definition($table);
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty: the columns belong to the earlier migration
        // that this one repairs, so dropping them here would be wrong.
    }
};
