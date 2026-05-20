<?php

namespace App\Services\GoogleMaps;

interface GoogleMapsClient
{
    public function browserConfig(): array;

    public function geocodePlace(string $placeId): array;

    public function reverseGeocode(float $latitude, float $longitude): array;

    public function distanceInMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float;
}
