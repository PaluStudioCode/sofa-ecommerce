<?php

namespace App\Support;

class GeoDistance
{
    public static function haversineMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadius = 6371000;

        $fromLat = deg2rad($fromLatitude);
        $toLat = deg2rad($toLatitude);
        $deltaLat = deg2rad($toLatitude - $fromLatitude);
        $deltaLng = deg2rad($toLongitude - $fromLongitude);

        $angle = sin($deltaLat / 2) ** 2
            + cos($fromLat) * cos($toLat) * (sin($deltaLng / 2) ** 2);

        return $earthRadius * 2 * atan2(sqrt($angle), sqrt(1 - $angle));
    }
}
