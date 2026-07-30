<?php

namespace App\Providers;

use App\Support\PublicStorageLink;
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
         
    }
}
