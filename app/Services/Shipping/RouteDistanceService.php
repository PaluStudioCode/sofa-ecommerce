<?php

namespace App\Services\Shipping;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RouteDistanceService
{
    public function drivingDistance(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): array
    {
        $cacheKey = sprintf(
            'shipping.route_distance.osrm.v2.%s.%s.%s.%s',
            number_format($fromLatitude, 5, '.', ''),
            number_format($fromLongitude, 5, '.', ''),
            number_format($toLatitude, 5, '.', ''),
            number_format($toLongitude, 5, '.', '')
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes((int) config('services.routing.cache_ttl_minutes', 360)),
            fn () => $this->fetchOsrmDistance($fromLatitude, $fromLongitude, $toLatitude, $toLongitude)
        );
    }

    private function fetchOsrmDistance(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): array
    {
        $coordinates = implode(';', [
            $this->coordinatePair($fromLongitude, $fromLatitude),
            $this->coordinatePair($toLongitude, $toLatitude),
        ]);

        $url = rtrim((string) config('services.routing.osrm_base_url', 'https://router.project-osrm.org'), '/')
            .'/route/v1/driving/'
            .$coordinates;

        $response = Http::acceptJson()
            ->withUserAgent($this->userAgent())
            ->connectTimeout((int) config('services.routing.connect_timeout', 2))
            ->timeout((int) config('services.routing.timeout', 5))
            ->get($url, [
                'overview' => 'full',
                'geometries' => 'geojson',
                'alternatives' => 'false',
                'steps' => 'false',
            ])
            ->throw();

        $payload = $response->json();
        $route = $payload['routes'][0] ?? null;

        if (($payload['code'] ?? null) !== 'Ok' || ! is_array($route) || ! isset($route['distance'])) {
            throw new RuntimeException('Jarak tempuh jalan belum dapat dihitung dari OpenStreetMap.');
        }

        return [
            'provider' => 'osrm',
            'distance_meters' => (float) $route['distance'],
            'duration_seconds' => isset($route['duration']) ? (float) $route['duration'] : null,
            'route_geometry' => $this->routeGeometry($route),
        ];
    }

    private function routeGeometry(array $route): array
    {
        $coordinates = $route['geometry']['coordinates'] ?? [];

        if (! is_array($coordinates)) {
            return [];
        }

        return collect($coordinates)
            ->filter(fn ($coordinate) => is_array($coordinate)
                && isset($coordinate[0], $coordinate[1])
                && is_numeric($coordinate[0])
                && is_numeric($coordinate[1]))
            ->map(fn ($coordinate) => [
                'latitude' => (float) $coordinate[1],
                'longitude' => (float) $coordinate[0],
            ])
            ->values()
            ->all();
    }

    private function coordinatePair(float $longitude, float $latitude): string
    {
        return number_format($longitude, 8, '.', '').','.number_format($latitude, 8, '.', '');
    }

    private function userAgent(): string
    {
        $name = str_replace(["\r", "\n"], '', (string) config('app.name', 'Laravel'));
        $url = str_replace(["\r", "\n"], '', (string) config('app.url', 'http://localhost'));

        return "{$name}/1.0 ({$url})";
    }
}
