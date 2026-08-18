<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Products join the polymorphic images table: the product_images gallery and
     * the denormalised products.image column both become rows, then both go.
     *
     * product_images.product_variant_id is dropped without a home — it was never
     * written by any code path and holds no rows.
     */
    public function up(): void
    {
        $this->backfillGallery();
        $this->backfillOrphanProductColumns();

        Schema::dropIfExists('product_images');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }

    public function down(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('stock');
        });

        $rows = [];

        DB::table('images')
            ->where('imageable_type', 'product')
            ->where('collection', 'gallery')
            ->orderBy('id')
            ->each(function ($image) use (&$rows) {
                $rows[] = [
                    'product_id' => $image->imageable_id,
                    'product_variant_id' => null,
                    'path' => $image->path,
                    'alt' => $image->alt,
                    'sort_order' => $image->sort_order,
                    'is_primary' => $image->is_primary,
                    'created_at' => $image->created_at,
                    'updated_at' => $image->updated_at,
                ];

                if ($image->is_primary) {
                    DB::table('products')
                        ->where('id', $image->imageable_id)
                        ->update(['image' => $image->path]);
                }
            });

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('product_images')->insert($chunk);
        }

        DB::table('images')->where('imageable_type', 'product')->delete();
    }

    private function backfillGallery(): void
    {
        $rows = [];

        DB::table('product_images')
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->each(function ($image) use (&$rows) {
                $rows[] = [
                    'imageable_type' => 'product',
                    'imageable_id' => $image->product_id,
                    'collection' => 'gallery',
                    'disk' => 'public',
                    'path' => $image->path,
                    'alt' => $image->alt,
                    'sort_order' => $image->sort_order,
                    'is_primary' => $image->is_primary,
                    'created_at' => $image->created_at ?? now(),
                    'updated_at' => $image->updated_at ?? now(),
                ];
            });

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('images')->insert($chunk);
        }
    }

    /**
     * Some products carry an image only on the legacy column, with no gallery
     * row at all. Without this they would silently lose their picture.
     */
    private function backfillOrphanProductColumns(): void
    {
        $withGallery = DB::table('product_images')->distinct()->pluck('product_id')->all();
        $now = now();
        $rows = [];

        DB::table('products')
            ->select('id', 'image')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->when($withGallery, fn ($query) => $query->whereNotIn('id', $withGallery))
            ->orderBy('id')
            ->each(function ($product) use ($now, &$rows) {
                $rows[] = [
                    'imageable_type' => 'product',
                    'imageable_id' => $product->id,
                    'collection' => 'gallery',
                    'disk' => 'public',
                    'path' => $product->image,
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
    }
};
