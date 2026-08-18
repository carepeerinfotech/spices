<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment methods are now decided by the store-wide payment settings alone,
     * so products no longer carry their own COD/online flags. `tax_class` goes
     * with them: GST comes from the commerce settings, and nothing ever read it.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['allow_cod', 'allow_online', 'tax_class']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('tax_class')->default('gst_18')->after('hsn_code');
            $table->boolean('allow_cod')->default(true)->after('is_active');
            $table->boolean('allow_online')->default(true)->after('allow_cod');
        });
    }
};
