<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingSetting;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShippingAreaController extends Controller
{
    public function index(Request $request): Response
    {
        $setting = $this->currentSetting();

        return Inertia::render('Admin/ShippingAreas/Index', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Pengaturan Ongkir', 'href' => route('admin.shipping-areas.index')],
            ],
            'setting' => $setting ? $this->payload($setting) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $setting = $this->currentSetting();
        $data = $this->validatedData($request, $setting);

        $this->saveSetting($setting, $data);

        return back()->with('success', 'Pengaturan ongkir disimpan.');
    }

    public function update(Request $request, ShippingSetting $shippingArea): RedirectResponse
    {
        $data = $this->validatedData($request, $shippingArea);

        $this->saveSetting($shippingArea, $data);

        return back()->with('success', 'Pengaturan ongkir diperbarui.');
    }

    private function validatedData(Request $request, ?ShippingSetting $shippingSetting = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'latitude' => [$shippingSetting ? 'nullable' : 'required', 'numeric', 'between:-90,90'],
            'longitude' => [$shippingSetting ? 'nullable' : 'required', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
        ], [], [
            'name' => 'nama titik asal',
            'description' => 'alamat/catatan titik asal',
            'latitude' => 'latitude titik asal',
            'longitude' => 'longitude titik asal',
            'radius_km' => 'batas jarak jalan maksimal',
            'shipping_cost' => 'tarif ongkir per KM',
        ]);

        $latitude = filled($data['latitude'] ?? null)
            ? (float) $data['latitude']
            : (float) $shippingSetting?->origin_latitude;
        $longitude = filled($data['longitude'] ?? null)
            ? (float) $data['longitude']
            : (float) $shippingSetting?->origin_longitude;

        return [
            'origin_name' => trim((string) $data['name']),
            'origin_address' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'origin_latitude' => $latitude,
            'origin_longitude' => $longitude,
            'radius_km' => (float) $data['radius_km'],
            'shipping_cost_per_km' => (float) $data['shipping_cost'],
            'is_active' => true,
        ];
    }

    private function saveSetting(?ShippingSetting $setting, array $data): ShippingSetting
    {
        return DB::transaction(function () use ($setting, $data) {
            if ($setting) {
                ShippingSetting::query()
                    ->whereKeyNot($setting->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                $setting->update($data);

                return $setting->fresh();
            }

            ShippingSetting::query()
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return ShippingSetting::create($data);
        });
    }

    private function currentSetting(): ?ShippingSetting
    {
        return ShippingSetting::query()
            ->where('is_active', true)
            ->latest()
            ->first()
            ?? ShippingSetting::query()->latest()->first();
    }

    private function payload(ShippingSetting $area): array
    {
        return [
            'id' => $area->id,
            'name' => $area->origin_name,
            'description' => $area->origin_address,
            'latitude' => (float) $area->origin_latitude,
            'longitude' => (float) $area->origin_longitude,
            'radius_km' => (float) $area->radius_km,
            'shipping_cost' => (float) $area->shipping_cost_per_km,
            'shipping_cost_per_km' => (float) $area->shipping_cost_per_km,
            'is_active' => $area->is_active,
            'center_summary' => number_format((float) $area->origin_latitude, 5).', '.number_format((float) $area->origin_longitude, 5),
        ];
    }
}
