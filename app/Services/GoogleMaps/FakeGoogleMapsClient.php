<?php

namespace App\Services\GoogleMaps;

class FakeGoogleMapsClient implements GoogleMapsClient
{
    public function browserConfig(): array
    {
        return [
            'apiKey' => config('services.google_maps.browser_key', 'fake-google-maps-key'),
            'mapId' => config('services.google_maps.map_id'),
        ];
    }

    public function geocodePlace(string $placeId): array
    {
        return $this->fakeGeocodeResponse($placeId, -6.2, 106.816666);
    }

    public function reverseGeocode(float $latitude, float $longitude): array
    {
        return $this->fakeGeocodeResponse('fake-reverse-geocode', $latitude, $longitude);
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

    private function fakeGeocodeResponse(string $placeId, float $latitude, float $longitude): array
    {
        return [
            'status' => 'OK',
            'results' => [
                [
                    'place_id' => $placeId,
                    'formatted_address' => 'Jl. Contoh Sofa No. 1, Jakarta, Indonesia',
                    'geometry' => [
                        'location' => [
                            'lat' => $latitude,
                            'lng' => $longitude,
                        ],
                    ],
                    'address_components' => [],
                ],
            ],
        ];
    }
}
