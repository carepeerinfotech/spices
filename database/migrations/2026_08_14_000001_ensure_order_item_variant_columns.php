<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Re-adds the `order_items` columns introduced by
     * 2026_07_18_100001_expand_catalog_for_variants_and_media, for databases
     * where that migration is recorded as run but the columns are absent
     * (e.g. restored from a dump predating it). Adds only what is missing, so
     * it is a no-op on databases that are already correct.
     *
     * Sibling of 2026_08_04_000000_ensure_order_billing_columns, which repairs
     * the `orders` half of the same migration.
     */
    public function up(): void
    {
        $existing = Schema::getColumnListing('order_items');

        // Declaration order matters: `after()` anchors may themselves be
        // added by this same ALTER statement.
        $columns = [
            'product_variant_id' => fn (Blueprint $t) => $t->unsignedBigInteger('product_variant_id')->nullable()->after('product_id'),
            'variant_label' => fn (Blueprint $t) => $t->string('variant_label')->nullable()->after('product_sku'),
            'variant_options' => fn (Blueprint $t) => $t->json('variant_options')->nullable()->after('variant_label'),
            'weight' => fn (Blueprint $t) => $t->decimal('weight', 8, 3)->nullable()->after('total'),
        ];

        $missing = array_diff_key($columns, array_flip($existing));

        if ($missing === []) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) use ($missing) {
            foreach ($missing as $definition) {
                $definition($table);
            }
        });

        // The column carries a foreign key in the original migration; add it
        // only alongside a freshly created column, so a database that already
        // has the constraint is left untouched.
        if (isset($missing['product_variant_id']) && Schema::hasTable('product_variants')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: the columns belong to the earlier migration
        // that this one repairs, so dropping them here would be wrong.
    }
};
