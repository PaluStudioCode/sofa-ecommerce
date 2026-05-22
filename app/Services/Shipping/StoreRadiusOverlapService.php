<?php

namespace App\Services\Shipping;

use App\Models\Store;
use App\Support\GeoDistance;

class StoreRadiusOverlapService
{
    public function conflictingStore(array $candidate, ?int $ignoreStoreId = null): ?Store
    {
        if (! ($candidate['is_active'] ?? false)) {
            return null;
        }

        return Store::query()
            ->where('is_active', true)
            ->where('priority', (int) $candidate['priority'])
            ->when($ignoreStoreId, fn ($query) => $query->whereKeyNot($ignoreStoreId))
            ->get()
            ->first(function (Store $store) use ($candidate) {
                $distance = GeoDistance::haversineMeters(
                    (float) $candidate['latitude'],
                    (float) $candidate['longitude'],
                    (float) $store->latitude,
                    (float) $store->longitude,
                );

                $combinedRadius = (((float) $candidate['radius_km']) + ((float) $store->radius_km)) * 1000;

                return $distance <= $combinedRadius;
            });
    }
}
