<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingSetting;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ShippingAreaController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', Rule::in(['', '1', '0'])],
        ]);

        $areas = ShippingSetting::query()
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('origin_name', 'like', "%{$keyword}%")
                        ->orWhere('origin_address', 'like', "%{$keyword}%");
                });
            })
            ->when(($filters['is_active'] ?? '') !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->orderByDesc('is_active')
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ShippingSetting $area) => $this->payload($area));

        $currentRule = ShippingSetting::query()
            ->where('is_active', true)
            ->latest()
            ->first()
            ?? ShippingSetting::query()->latest()->first();

        return Inertia::render('Admin/ShippingAreas/Index', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Aturan Ongkir Radius', 'href' => route('admin.shipping-areas.index')],
            ],
            'areas' => $areas,
            'currentRule' => $currentRule ? $this->payload($currentRule) : null,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
                'is_active' => $filters['is_active'] ?? '',
            ],
            'activeOptions' => [
                ['value' => '', 'label' => 'Semua status'],
                ['value' => '1', 'label' => 'Aktif'],
                ['value' => '0', 'label' => 'Nonaktif'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data) {
            if ($data['is_active']) {
                ShippingSetting::query()->where('is_active', true)->update(['is_active' => false]);
            }

            ShippingSetting::create($data);
        });

        return back()->with('success', 'Aturan ongkir radius disimpan.');
    }

    public function update(Request $request, ShippingSetting $shippingArea): RedirectResponse
    {
        $data = $this->validatedData($request, $shippingArea);

        DB::transaction(function () use ($shippingArea, $data) {
            if ($data['is_active']) {
                ShippingSetting::query()
                    ->whereKeyNot($shippingArea->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $shippingArea->update($data);
        });

        return back()->with('success', 'Aturan ongkir radius diperbarui.');
    }

    public function destroy(ShippingSetting $shippingArea): RedirectResponse
    {
        $shippingArea->delete();

        return back()->with('success', 'Aturan ongkir radius dihapus.');
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
            'is_active' => ['boolean'],
        ]);

        if ($shippingSetting && ! isset($data['latitude'], $data['longitude'])) {
            $data['latitude'] = (float) $shippingSetting->origin_latitude;
            $data['longitude'] = (float) $shippingSetting->origin_longitude;
        }

        return [
            'origin_name' => trim((string) $data['name']),
            'origin_address' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'origin_latitude' => (float) $data['latitude'],
            'origin_longitude' => (float) $data['longitude'],
            'radius_km' => (float) $data['radius_km'],
            'shipping_cost_per_km' => (float) $data['shipping_cost'],
            'is_active' => $request->boolean('is_active'),
        ];
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
