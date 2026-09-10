<?php

namespace App\Services;

class TanzaniaLocationService
{
    /** @return array<string, list<string>> */
    public function regionsWithDistricts(): array
    {
        return config('tanzania_locations', []);
    }

    /** @return list<string> */
    public function regions(): array
    {
        return array_keys($this->regionsWithDistricts());
    }

    /** @return list<string> */
    public function districtsFor(?string $region): array
    {
        if (! $region) {
            return [];
        }

        return $this->regionsWithDistricts()[$region] ?? [];
    }

    public function isValidRegion(?string $region): bool
    {
        return $region !== null && array_key_exists($region, $this->regionsWithDistricts());
    }

    public function isValidDistrict(?string $region, ?string $district): bool
    {
        if (! $this->isValidRegion($region) || $district === null) {
            return false;
        }

        return in_array($district, $this->districtsFor($region), true);
    }
}
