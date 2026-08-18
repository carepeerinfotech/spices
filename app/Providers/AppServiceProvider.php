<?php

namespace App\Providers;

use App\Support\PublicStorageLink;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
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

        // @asset('css/home.css') — like asset(), plus a ?v= mtime stamp so a deploy
        // invalidates the browser cache instead of waiting out the host's max-age.
        Blade::directive(
            'asset',
            fn (string $expression) => "<?php echo e(\App\Support\AssetVersion::url({$expression})); ?>"
        );
    }
}
