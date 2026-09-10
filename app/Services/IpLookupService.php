<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IpLookupService
{
    public function lookup(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        if ($this->isPrivateOrReservedIp($ip)) {
            return 'Local network';
        }

        return Cache::remember('ip_location:'.$ip, now()->addDays(30), function () use ($ip) {
            try {
                $response = Http::timeout(4)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,country,regionName,city',
                ]);

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json();
                if (($data['status'] ?? '') !== 'success') {
                    return null;
                }

                $parts = array_filter([
                    $data['city'] ?? null,
                    $data['regionName'] ?? null,
                    $data['country'] ?? null,
                ]);

                return $parts !== [] ? implode(', ', $parts) : null;
            } catch (\Throwable) {
                return null;
            }
        });
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
