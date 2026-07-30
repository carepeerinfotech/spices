<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $all = $this->group($group);

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function group(string $group): array
    {
        return Cache::remember("settings.group.{$group}", 300, function () use ($group) {
            $items = Setting::query()->where('group', $group)->get();
            $result = [];

            foreach ($items as $item) {
                $value = $item->value;

                if ($item->is_encrypted && filled($value)) {
                    try {
                        $value = Crypt::decryptString($value);
                    } catch (\Throwable) {
                        $value = null;
                    }
                }

                $result[$item->key] = $this->castValue($value, $item->type);
            }

            return $result;
        });
    }

    public function set(string $group, string $key, mixed $value, string $type = 'string', bool $encrypted = false): void
    {
        $stored = is_array($value) ? json_encode($value) : (string) ($value ?? '');

        if ($type === 'boolean') {
            $stored = $value ? '1' : '0';
        }

        if ($encrypted && filled($stored)) {
            $stored = Crypt::encryptString($stored);
        }

        Setting::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $stored, 'type' => $type, 'is_encrypted' => $encrypted]
        );

        Cache::forget("settings.group.{$group}");
    }

    public function setMany(string $group, array $values, array $meta = []): void
    {
        foreach ($values as $key => $value) {
            $type = $meta[$key]['type'] ?? (is_bool($value) ? 'boolean' : (is_array($value) ? 'json' : 'string'));
            $encrypted = (bool) ($meta[$key]['encrypted'] ?? false);

            // Keep existing secret when blank placeholder submitted
            if ($encrypted && ($value === null || $value === '')) {
                continue;
            }

            $this->set($group, $key, $value, $type, $encrypted);
        }
    }

    public function bool(string $group, string $key, bool $default = false): bool
    {
        return (bool) $this->get($group, $key, $default);
    }

    public function float(string $group, string $key, float $default = 0): float
    {
        return (float) $this->get($group, $key, $default);
    }

    public function masked(string $group, string $key): ?string
    {
        $value = $this->get($group, $key);

        if (! filled($value)) {
            return null;
        }

        $string = (string) $value;
        if (strlen($string) <= 4) {
            return str_repeat('*', strlen($string));
        }

        return str_repeat('*', max(strlen($string) - 4, 4)).substr($string, -4);
    }

    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => is_string($value) ? (json_decode($value, true) ?: []) : (array) $value,
            default => $value,
        };
    }
}
