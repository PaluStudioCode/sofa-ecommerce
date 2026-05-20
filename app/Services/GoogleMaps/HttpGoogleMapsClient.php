<?php

namespace App\Services\GoogleMaps;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class HttpGoogleMapsClient implements GoogleMapsClient
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function browserConfig(): array
    {
        return [
            'apiKey' => config('services.google_maps.browser_key'),
            'mapId' => config('services.google_maps.map_id'),
        ];
    }

    public function geocodePlace(string $placeId): array
    {
        return $this->geocode(['place_id' => $placeId]);
    }

    public function reverseGeocode(float $latitude, float $longitude): array
    {
        return $this->geocode(['latlng' => $latitude.','.$longitude]);
    }

    public function distanceInMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadius = 6371000;
        $fromLat = deg2rad($fromLatitude);
        $toLat = deg2rad($toLatitude);
        $deltaLat = deg2rad($toLatitude - $fromLatitude);
        $deltaLng = deg2rad($toLongitude - $fromLongitude);

        $angle = sin($deltaLat / 2) ** 2
            + cos($fromLat) * cos($toLat) * sin($deltaLng / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($angle), sqrt(1 - $angle)));
    }

    private function geocode(array $query): array
    {
        $apiKey = (string) config('services.google_maps.api_key', '');

        if ($apiKey === '') {
            throw new RuntimeException('Google Maps API key is not configured.');
        }

        return $this->http
            ->acceptJson()
            ->get(rtrim((string) config('services.google_maps.api_base_url'), '/').'/geocode/json', [
                ...$query,
                'key' => $apiKey,
            ])
            ->throw()
            ->json();
    }
}
