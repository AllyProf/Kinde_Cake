<?php

namespace App\Services;

use App\Models\Category;
use App\Models\PackageUnit;
use App\Models\PaymentProvider;

class CatalogService
{
    /** @return array<int, array<string, string>> */
    public function builtinCategories(): array
    {
        return config('catalog.categories', []);
    }

    /** @return array<int, array<string, string>> */
    public function builtinPackages(): array
    {
        return config('catalog.packages', []);
    }

    public function importCategories(): int
    {
        $imported = 0;

        foreach ($this->builtinCategories() as $item) {
            $exists = Category::where('slug', $item['slug'])->exists();
            if ($exists) {
                continue;
            }

            Category::create([
                'name' => $item['name'],
                'slug' => $item['slug'],
                'description' => $item['description'] ?? null,
                'is_builtin' => true,
                'is_active' => true,
            ]);

            $imported++;
        }

        return $imported;
    }

    public function importPackages(): int
    {
        $imported = 0;

        foreach ($this->builtinPackages() as $item) {
            $exists = PackageUnit::where('symbol', $item['symbol'])->exists();
            if ($exists) {
                continue;
            }

            PackageUnit::create([
                'name' => $item['name'],
                'symbol' => $item['symbol'],
                'description' => $item['description'] ?? null,
                'is_builtin' => true,
                'is_active' => true,
            ]);

            $imported++;
        }

        return $imported;
    }

    /** @return array<int, array<string, string>> */
    public function builtinPaymentProviders(): array
    {
        return config('catalog.payment_providers', []);
    }

    public function importPaymentProviders(): int
    {
        $imported = 0;

        foreach ($this->builtinPaymentProviders() as $item) {
            $exists = PaymentProvider::query()
                ->where('name', $item['name'])
                ->where('type', $item['type'])
                ->exists();

            if ($exists) {
                continue;
            }

            PaymentProvider::create([
                'name' => $item['name'],
                'type' => $item['type'],
                'is_builtin' => true,
                'is_active' => true,
            ]);

            $imported++;
        }

        return $imported;
    }
}
