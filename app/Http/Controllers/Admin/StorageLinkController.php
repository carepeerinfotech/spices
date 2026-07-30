<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PublicStorageLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class StorageLinkController extends Controller
{
    public function store(Request $request)
    {
        $result = PublicStorageLink::ensure();

        // Clear caches without shell — safe even when exec() is disabled for storage:link.
        $this->clearCachesQuietly();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $result['ok'],
                'message' => $result['message'],
                'method' => $result['method'],
                'redirect' => $result['ok'] ? route('admin.dashboard') : null,
            ], $result['ok'] ? 200 : 422);
        }

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['message']
        );
    }

    private function clearCachesQuietly(): void
    {
        foreach (['config:clear', 'route:clear', 'view:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (\Throwable $e) {
                // Ignore — some hosts block certain Artisan commands.
            }
        }

        // Also delete cached files directly if Artisan fails.
        foreach ([
            base_path('bootstrap/cache/config.php'),
            base_path('bootstrap/cache/routes-v7.php'),
            base_path('bootstrap/cache/routes.php'),
        ] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
