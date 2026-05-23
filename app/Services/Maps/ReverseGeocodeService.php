<?php

namespace App\Services\Maps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ReverseGeocodeService
{
    public function lookup(float $latitude, float $longitude): array
    {
        $cacheKey = sprintf('maps.reverse_geocode.%s.%s', number_format($latitude, 5, '.', ''), number_format($longitude, 5, '.', ''));

        return Cache::remember(
            $cacheKey,
            now()->addHours(12),
            fn () => $this->fetch($latitude, $longitude)
        );
    }

    private function fetch(float $latitude, float $longitude): array
    {
        $response = Http::acceptJson()
            ->withUserAgent($this->userAgent())
            ->connectTimeout(2)
            ->timeout(4)
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'jsonv2',
                'lat' => number_format($latitude, 8, '.', ''),
                'lon' => number_format($longitude, 8, '.', ''),
                'addressdetails' => 1,
                'zoom' => 18,
            ])
            ->throw();

        $payload = $response->json();
        $address = $payload['address'] ?? [];
        $fallback = number_format($latitude, 6, '.', '').', '.number_format($longitude, 6, '.', '');

        return [
            'formatted_address' => $payload['display_name'] ?? $payload['name'] ?? $fallback,
            'city' => $address['city']
                ?? $address['town']
                ?? $address['village']
                ?? $address['municipality']
                ?? $address['county']
                ?? $address['state']
                ?? '',
            'district' => $address['suburb']
                ?? $address['city_district']
                ?? $address['district']
                ?? $address['neighbourhood']
                ?? $address['hamlet']
                ?? '',
            'postal_code' => $address['postcode'] ?? '',
        ];
    }

    private function userAgent(): string
    {
        $name = str_replace(["\r", "\n"], '', (string) config('app.name', 'Laravel'));
        $url = str_replace(["\r", "\n"], '', (string) config('app.url', 'http://localhost'));

        return "{$name}/1.0 ({$url})";
    }
}
