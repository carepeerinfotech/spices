<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categories move off their `image` / `banner` string columns and onto the
     * polymorphic images table. Existing values are carried over as rows first,
     * then the columns are dropped.
     */
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('imageable_type');
            $table->unsignedBigInteger('imageable_id');
            $table->string('collection')->default('default');
            $table->string('disk')->default('public');
            // Long enough to hold an external URL as well as a stored path.
            $table->string('path', 1000);
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(
                ['imageable_type', 'imageable_id', 'collection', 'sort_order'],
                'images_owner_collection_index'
            );
        });

        $this->backfillCategories();

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['image', 'banner']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
            $table->string('banner')->nullable()->after('image');
        });

        foreach (['image', 'banner'] as $collection) {
            DB::table('images')
                ->where('imageable_type', 'category')
                ->where('collection', $collection)
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->get()
                ->reverse() // Lowest priority first, so the best row wins the column.
                ->each(function ($image) use ($collection) {
                    DB::table('categories')
                        ->where('id', $image->imageable_id)
                        ->update([$collection => $image->path]);
                });
        }

        Schema::dropIfExists('images');
    }

    private function backfillCategories(): void
    {
        $now = now();
        $rows = [];

        foreach (['image', 'banner'] as $collection) {
            DB::table('categories')
                ->select('id', $collection)
                ->whereNotNull($collection)
                ->where($collection, '!=', '')
                ->orderBy('id')
                ->each(function ($category) use ($collection, $now, &$rows) {
                    $rows[] = [
                        'imageable_type' => 'category',
                        'imageable_id' => $category->id,
                        'collection' => $collection,
                        'disk' => 'public',
                        'path' => $category->{$collection},
                        'alt' => null,
                        'sort_order' => 0,
                        'is_primary' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                });
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('images')->insert($chunk);
        }
    }
};
