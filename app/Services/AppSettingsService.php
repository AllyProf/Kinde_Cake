<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class AppSettingsService
{
    private const CACHE_KEY = 'app.settings.all';

    public function get(string $key, ?string $default = null): ?string
    {
        $settings = $this->all();

        if (array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        return $default ?? config("app_settings.{$key}");
    }

    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = Setting::pluck('value', 'key')->all();

            return array_merge(config('app_settings', []), $stored);
        });
    }

    public function brandColor(): string
    {
        $default = config('app_settings.brand_color', '#7c461f');
        $color = $this->get('brand_color', $default) ?? $default;

        return $this->normalizeHexColor($color) ?? $default;
    }

    public function brandColorDark(): string
    {
        return $this->darkenHex($this->brandColor(), 0.18);
    }

    /** @return array<string, string> */
    public function themeVariables(): array
    {
        $brand = $this->brandColor();

        return [
            'brand' => $brand,
            'brand-dark' => $this->brandColorDark(),
            'brand-rgb' => $this->hexToRgbComponents($brand),
            'white' => $this->normalizeHexColor($this->get('brand_white', '#ffffff') ?? '#ffffff') ?? '#ffffff',
            'black' => $this->normalizeHexColor($this->get('brand_black', '#000000') ?? '#000000') ?? '#000000',
        ];
    }

    public function hexToRgbComponents(string $hex): string
    {
        $hex = ltrim($this->normalizeHexColor($hex) ?? config('app_settings.brand_color', '#7c461f'), '#');

        return sprintf(
            '%d, %d, %d',
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );
    }

    public function normalizeHexColor(string $color): ?string
    {
        $color = trim($color);

        if (preg_match('/^#([A-Fa-f0-9]{6})$/', $color, $matches)) {
            return '#'.strtolower($matches[1]);
        }

        if (preg_match('/^#([A-Fa-f0-9]{3})$/', $color, $matches)) {
            $hex = $matches[1];

            return '#'.strtolower($hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]);
        }

        return null;
    }

    public function darkenHex(string $hex, float $amount = 0.15): string
    {
        $hex = ltrim($this->normalizeHexColor($hex) ?? config('app_settings.brand_color', '#7c461f'), '#');
        $amount = max(0, min(1, $amount));

        $r = max(0, (int) round(hexdec(substr($hex, 0, 2)) * (1 - $amount)));
        $g = max(0, (int) round(hexdec(substr($hex, 2, 2)) * (1 - $amount)));
        $b = max(0, (int) round(hexdec(substr($hex, 4, 2)) * (1 - $amount)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
