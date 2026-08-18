<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['collection', 'disk', 'path', 'alt', 'sort_order', 'is_primary'])]
class Image extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The row owns the file, so deleting the record always removes the blob.
        // No caller has to remember to clean up, and none can orphan an upload.
        static::deleted(fn (Image $image) => $image->deleteFile());
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(): ?string
    {
        return MediaUrl::public($this->path);
    }

    /**
     * External URLs and host-absolute paths are referenced, not stored by us,
     * so they must never be handed to the filesystem.
     */
    public function isRemote(): bool
    {
        return str_starts_with($this->path, 'http://')
            || str_starts_with($this->path, 'https://')
            || str_starts_with($this->path, '/');
    }

    /**
     * A readable filename for downloads — stored paths use a random hash, which
     * makes a poor thing to land in someone's Downloads folder.
     */
    public function downloadName(): string
    {
        $path = parse_url($this->path, PHP_URL_PATH) ?: $this->path;
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';

        $owner = $this->imageable;
        $label = $owner?->name ?? $owner?->title ?? class_basename($owner ?? static::class);

        return Str::slug($label.' '.$this->collection).'.'.$extension;
    }

    public function deleteFile(): void
    {
        if ($this->isRemote()) {
            return;
        }

        Storage::disk($this->disk ?: config('media.disk', 'public'))->delete($this->path);
    }

    public function scopeInCollection(Builder $query, string $collection): Builder
    {
        return $query->where('collection', $collection);
    }
}
