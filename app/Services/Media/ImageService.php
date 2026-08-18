<?php

namespace App\Services\Media;

use App\Models\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * The single place image uploads are validated, stored, replaced and removed.
 *
 * Controllers never touch the filesystem: they ask for rules() and then call
 * syncFromRequest(). Everything else about a model's images is driven by its
 * entry in config/media.php.
 */
class ImageService
{
    /**
     * Validation rules for every collection the owner declares, keyed by the
     * form field name so they can be merged straight into a $request->validate().
     *
     * @return array<string, array<int, string>>
     */
    public function rules(Model|string $owner): array
    {
        $rules = [];

        foreach ($this->collections($owner) as $collection => $config) {
            $max = (int) ($config['max_kb'] ?? 4096);

            if ($config['multiple'] ?? false) {
                $rules[$collection.'_files'] = ['nullable', 'array'];
                $rules[$collection.'_files.*'] = ['image', 'max:'.$max];
            } else {
                $rules[$collection.'_file'] = ['nullable', 'image', 'max:'.$max];
            }
        }

        return $rules;
    }

    /**
     * Apply whatever the form uploaded. Single collections replace their current
     * image; multiple collections append. Removal is not handled here — the
     * admin UI deletes images outright through the images.destroy endpoint.
     */
    public function syncFromRequest(Model $owner, Request $request): void
    {
        foreach ($this->collections($owner) as $collection => $config) {
            if ($config['multiple'] ?? false) {
                foreach ($request->file($collection.'_files', []) ?: [] as $file) {
                    if ($file instanceof UploadedFile) {
                        $this->attach($owner, $collection, $file);
                    }
                }

                continue;
            }

            if ($request->hasFile($collection.'_file')) {
                $this->replace($owner, $collection, $request->file($collection.'_file'));
            }
        }
    }

    /**
     * Store a file (or record an already-hosted URL) against the owner.
     */
    public function attach(Model $owner, string $collection, UploadedFile|string $file, array $attributes = []): Image
    {
        $config = $this->config($owner, $collection);
        $disk = $config['disk'] ?? config('media.disk', 'public');

        $path = $file instanceof UploadedFile
            ? $file->store($this->directory($owner, $config), $disk)
            : $file;

        $existing = $owner->images()->where('collection', $collection);

        $image = $owner->images()->create([
            'collection' => $collection,
            'disk' => $disk,
            'path' => $path,
            'alt' => $attributes['alt'] ?? null,
            'sort_order' => $attributes['sort_order'] ?? ((int) $existing->max('sort_order') + 1),
            'is_primary' => $attributes['is_primary'] ?? ! $existing->exists(),
        ]);

        $owner->unsetRelation('images');

        return $image;
    }

    /**
     * Swap a single-image collection: the old row (and its file) goes away only
     * once the replacement is on disk.
     */
    public function replace(Model $owner, string $collection, UploadedFile|string|null $file): ?Image
    {
        if ($file === null) {
            return null;
        }

        $previous = $owner->images()->where('collection', $collection)->get();
        $image = $this->attach($owner, $collection, $file, ['is_primary' => true, 'sort_order' => 0]);
        $previous->each->delete();

        $owner->unsetRelation('images');

        return $image;
    }

    public function clear(Model $owner, ?string $collection = null): void
    {
        $query = $owner->images();

        if ($collection !== null) {
            $query->where('collection', $collection);
        }

        $query->get()->each->delete();

        $owner->unsetRelation('images');
    }

    /**
     * Persist a new display order. Which image is primary is chosen separately,
     * so reordering never steals that flag — but a collection is left with one.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Model $owner, string $collection, array $orderedIds): void
    {
        $images = $owner->images()->where('collection', $collection)->get()->keyBy('id');

        foreach (array_values($orderedIds) as $position => $id) {
            $images->get($id)?->update(['sort_order' => $position]);
        }

        if ($images->isNotEmpty() && $images->every(fn (Image $image) => ! $image->is_primary)) {
            $images->sortBy('sort_order')->first()->update(['is_primary' => true]);
        }

        $owner->unsetRelation('images');
    }

    public function setPrimary(Image $image): void
    {
        Image::query()
            ->where('imageable_type', $image->imageable_type)
            ->where('imageable_id', $image->imageable_id)
            ->where('collection', $image->collection)
            ->update(['is_primary' => false]);

        $image->update(['is_primary' => true]);
    }

    /**
     * Permission slug that guards every image belonging to this owner type.
     */
    public function permissionFor(Model|string $owner): ?string
    {
        return $this->owner($owner)['permission'] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function collections(Model|string $owner): array
    {
        return $this->owner($owner)['collections'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function config(Model|string $owner, string $collection): array
    {
        $collections = $this->collections($owner);

        if (! isset($collections[$collection])) {
            throw new InvalidArgumentException(sprintf(
                'Image collection [%s] is not registered for [%s] in config/media.php.',
                $collection,
                is_string($owner) ? $owner : $owner::class,
            ));
        }

        return $collections[$collection];
    }

    /**
     * Upload target for this collection, with "{id}" resolved to the owner's key
     * so records can keep their files in their own folder.
     *
     * @param  array<string, mixed>  $config
     */
    private function directory(Model $owner, array $config): string
    {
        return str_replace(
            '{id}',
            (string) $owner->getKey(),
            $config['directory'] ?? 'uploads'
        );
    }

    /**
     * Resolve an owner definition from a model, a class name or a morph alias.
     *
     * @return array<string, mixed>
     */
    private function owner(Model|string $owner): array
    {
        $owners = config('media.owners', []);

        if (is_string($owner) && isset($owners[$owner])) {
            return $owners[$owner];
        }

        $class = is_string($owner) ? $owner : $owner::class;

        foreach ($owners as $definition) {
            if (($definition['model'] ?? null) === $class) {
                return $definition;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'No image owner registered for [%s] in config/media.php.',
            $class,
        ));
    }
}
