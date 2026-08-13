<?php

namespace App\Support;

class AssetVersion
{
    /** @var array<string, string> */
    private static array $cache = [];

    /**
     * Public asset URL stamped with the file's mtime.
     *
     * The host serves css/js with a 7 day max-age and no revalidation, so without a
     * changing query string returning visitors keep the pre-deploy copy for a week.
     */
    public static function url(string $path): string
    {
        return self::$cache[$path] ??= self::build($path);
    }

    private static function build(string $path): string
    {
        $url = asset($path);
        $file = public_path($path);

        if (! is_file($file)) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'v='.filemtime($file);
    }
}
