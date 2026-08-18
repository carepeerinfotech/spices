<?php

namespace App\Models\Concerns;

use App\Models\Image;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Gives any model named image collections backed by the polymorphic images
 * table. Pair it with an entry in config/media.php to make the shared admin
 * upload field, validation and delete endpoint work for that model.
 */
trait HasImages
{
    public static function bootHasImages(): void
    {
        static::deleting(function ($model) {
            // Deleted one by one so each Image's own hook removes its file.
            $model->images()->get()->each->delete();
        });
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Reads through the loaded relation so a page rendering several
     * collections costs one query, not one per collection.
     */
    public function imagesIn(string $collection): Collection
    {
        return $this->images
            ->where('collection', $collection)
            ->sortByDesc('is_primary')
            ->values();
    }

    public function image(string $collection = 'default'): ?Image
    {
        return $this->imagesIn($collection)->first();
    }

    public function imageUrlFor(string $collection = 'default'): ?string
    {
        return $this->image($collection)?->url();
    }
}
