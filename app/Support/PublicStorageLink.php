<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Shared-host storage: use a REAL public/storage folder (not a symlink).
 * Many hosts allow storage:link to "exist" but block following the symlink,
 * so /storage/... 404s even though files are in storage/app/public.
 */
class PublicStorageLink
{
    public static function linkPath(): string
    {
        return public_path('storage');
    }

    public static function legacyTargetPath(): string
    {
        return storage_path('app/public');
    }

    public static function isReady(): bool
    {
        $path = self::linkPath();

        return is_dir($path) && ! is_link($path) && is_writable($path);
    }

    /**
     * @return array{ok: bool, message: string, method: string|null}
     */
    public static function ensure(): array
    {
        $publicStorage = self::linkPath();
        $legacy = self::legacyTargetPath();

        if (! is_dir($legacy)) {
            @mkdir($legacy, 0755, true);
        }

        // Remove broken / working symlink — hosts often can't follow it.
        if (is_link($publicStorage)) {
            @unlink($publicStorage);
        }

        // Create real directory
        if (! is_dir($publicStorage)) {
            if (! @mkdir($publicStorage, 0755, true) && ! is_dir($publicStorage)) {
                return [
                    'ok' => false,
                    'message' => 'Could not create public/storage. Set permissions 755 on the public folder.',
                    'method' => null,
                ];
            }
        }

        if (is_link($publicStorage)) {
            return [
                'ok' => false,
                'message' => 'public/storage is still a symlink. Delete it in the file manager, then click Fix again.',
                'method' => null,
            ];
        }

        self::ensureGitignore($publicStorage);

        // Move any files previously stored under storage/app/public
        $moved = self::mirrorLegacyFiles($legacy, $publicStorage);

        if (! is_writable($publicStorage)) {
            return [
                'ok' => false,
                'message' => 'public/storage exists but is not writable. Set permissions to 755 or 775.',
                'method' => null,
            ];
        }

        $extra = $moved > 0 ? " Moved {$moved} file(s) from storage/app/public." : '';

        return [
            'ok' => true,
            'message' => 'Public storage is ready (real folder, no symlink).'.$extra.' Re-upload images if an old file is still missing.',
            'method' => 'directory',
        ];
    }

    private static function ensureGitignore(string $dir): void
    {
        $gitignore = $dir.DIRECTORY_SEPARATOR.'.gitignore';
        if (! is_file($gitignore)) {
            @file_put_contents($gitignore, "*\n!.gitignore\n");
        }
    }

    private static function mirrorLegacyFiles(string $from, string $to): int
    {
        if (! is_dir($from) || realpath($from) === realpath($to)) {
            return 0;
        }

        $count = 0;

        try {
            foreach (File::allFiles($from) as $file) {
                if ($file->getFilename() === '.gitignore') {
                    continue;
                }

                $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen(realpath($from)))), '/');
                $destination = $to.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

                if (is_file($destination)) {
                    continue;
                }

                $dir = dirname($destination);
                if (! is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }

                if (@copy($file->getPathname(), $destination)) {
                    $count++;
                }
            }
        } catch (\Throwable $e) {
            // Best-effort copy
        }

        return $count;
    }
}
