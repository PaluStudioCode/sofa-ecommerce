<?php

namespace App\Http\Controllers;

use App\Services\Maps\ReverseGeocodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MapReverseGeocodeController extends Controller
{
    public function __invoke(Request $request, ReverseGeocodeService $geocoder): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        try {
            $details = $geocoder->lookup($latitude, $longitude);
        } catch (Throwable $exception) {
            Log::warning('Maps reverse geocode lookup failed while selecting an address.', [
                'user_id' => $request->user()?->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Alamat lokasi belum dapat diambil.',
            ], 502);
        }

        return response()->json($details);
    }
}
