<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The last image column: product_variants.image becomes a row in the
     * polymorphic table, so every image in the app lives in one place.
     */
    public function up(): void
    {
        $now = now();
        $rows = [];

        DB::table('product_variants')
            ->select('id', 'image')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('id')
            ->each(function ($variant) use ($now, &$rows) {
                $rows[] = [
                    'imageable_type' => 'product_variant',
                    'imageable_id' => $variant->id,
                    'collection' => 'image',
                    'disk' => 'public',
                    'path' => $variant->image,
                    'alt' => null,
                    'sort_order' => 0,
                    'is_primary' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('images')->insert($chunk);
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('image')->nullable()->after('stock');
        });

        DB::table('images')
            ->where('imageable_type', 'product_variant')
            ->where('collection', 'image')
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->get()
            ->reverse() // Lowest priority first, so the best row wins the column.
            ->each(function ($image) {
                DB::table('product_variants')
                    ->where('id', $image->imageable_id)
                    ->update(['image' => $image->path]);
            });

        DB::table('images')->where('imageable_type', 'product_variant')->delete();
    }
};
