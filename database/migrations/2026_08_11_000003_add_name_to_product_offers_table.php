<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->string('name')->nullable()->after('source_offer_id');
        });

        // Copies now carry the flag too: it marks membership of a category-wide
        // offer, so the checkbox shows ticked on every product it reaches.
        DB::table('product_offers')->whereNotNull('source_offer_id')->update(['apply_to_category' => true]);
    }

    public function down(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
