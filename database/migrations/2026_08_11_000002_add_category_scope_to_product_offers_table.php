<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->boolean('apply_to_category')->default(false)->after('value');
            // Copies made for sibling products point back at the offer they came from.
            $table->foreignId('source_offer_id')->nullable()->after('user_id')
                ->constrained('product_offers')->cascadeOnDelete();
            $table->unique(['source_offer_id', 'product_id'], 'product_offers_source_product_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->dropUnique('product_offers_source_product_unique');
            $table->dropConstrainedForeignId('source_offer_id');
            $table->dropColumn('apply_to_category');
        });
    }
};
