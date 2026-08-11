<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'shipping_name')) {
            return;
        }

        $anchor = Schema::hasColumn('orders', 'billing_same_as_shipping')
            ? 'billing_same_as_shipping'
            : null;

        Schema::table('orders', function (Blueprint $table) use ($anchor) {
            $column = $table->string('shipping_name')->nullable();

            if ($anchor) {
                $column->after($anchor);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_name');
        });
    }
};
