<?php

namespace App\Providers;

use App\Support\PublicStorageLink;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Do NOT call Artisan::call('storage:link') — shared hosts often block exec()
     * and symlink following. We use a real public/storage directory instead.
     */
    public function boot(): void
    {
        // Keeps images.imageable_type as a short alias ('category') instead of a
        // PHP class name, so models can be renamed or moved without a migration.
        Relation::morphMap(
            collect(config('media.owners', []))
                ->map(fn (array $owner) => $owner['model'])
                ->all()
        );
    }
}
