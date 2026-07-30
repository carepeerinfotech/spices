<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class MediaUrl
{
    /**
     * Host-relative public media URL (uses FILESYSTEM_PUBLIC_URL when set).
     */
    public static function public(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        $base = parse_url((string) config('filesystems.disks.public.url'), PHP_URL_PATH) ?: '/storage';

        return rtrim($base, '/').'/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    public static function exists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }
}
