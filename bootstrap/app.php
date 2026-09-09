<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Drains the mail (and any other) queue without a persistent worker —
        // just the one cron entry shared hosting already supports:
        // * * * * * php artisan schedule:run. No-op when QUEUE_CONNECTION=sync,
        // since jobs run inline at dispatch and never reach the queue table.
        $schedule->command('queue:work --stop-when-empty --max-time=50 --tries=3')
            ->everyMinute()
            ->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'feature' => \App\Http\Middleware\EnsureFeatureEnabled::class,
            // Overrides the framework alias so the email verification feature flag applies.
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });

        $middleware->validateCsrfTokens(except: [
            'payments/paytm/callback',
            'webhooks/courier/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson() || $request->is('api/*') || $request->ajax(),
        );
    })->create();
