<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Shipping\StoreRadiusOverlapService;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ShippingAreaController extends Controller
{
    public function __construct(
        private readonly StoreRadiusOverlapService $overlaps,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', Rule::in(['', '1', '0'])],
        ]);

        $areas = Store::query()
            ->withCount('orders')
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                });
            })
            ->when(($filters['is_active'] ?? '') !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->orderByDesc('priority')
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Store $area) => $this->payload($area));

        return Inertia::render('Admin/ShippingAreas/Index', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Toko & Radius Layanan', 'href' => route('admin.shipping-areas.index')],
            ],
            'areas' => $areas,
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
        Store::create($this->validatedData($request));

        return back()->with('success', 'Toko dan radius layanan disimpan.');
    }

    public function update(Request $request, Store $shippingArea): RedirectResponse
    {
        $shippingArea->update($this->validatedData($request, $shippingArea));

        return back()->with('success', 'Toko dan radius layanan diperbarui.');
    }

    public function destroy(Store $shippingArea): RedirectResponse
    {
        if ($shippingArea->orders()->exists()) {
            $shippingArea->update(['is_active' => false]);

            return back()->with('success', 'Toko sudah pernah dipakai order, status diubah menjadi nonaktif.');
        }

        $shippingArea->delete();

        return back()->with('success', 'Toko dan radius layanan dihapus.');
    }

    private function validatedData(Request $request, ?Store $store = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'latitude' => [$store ? 'nullable' : 'required', 'numeric', 'between:-90,90'],
            'longitude' => [$store ? 'nullable' : 'required', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
            'priority' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if ($store && ! isset($data['latitude'], $data['longitude'])) {
            $data['latitude'] = (float) $store->latitude;
            $data['longitude'] = (float) $store->longitude;
        }

        $conflict = $this->overlaps->conflictingStore($data, $store?->id);

        if ($conflict) {
            throw ValidationException::withMessages([
                'priority' => "Radius aktif overlap dengan {$conflict->name} yang memiliki priority sama.",
            ]);
        }

        return $data;
    }

    private function payload(Store $area): array
    {
        return [
            'id' => $area->id,
            'name' => $area->name,
            'description' => $area->description,
            'latitude' => (float) $area->latitude,
            'longitude' => (float) $area->longitude,
            'radius_km' => (float) $area->radius_km,
            'shipping_cost' => (float) $area->shipping_cost,
            'priority' => $area->priority,
            'is_active' => $area->is_active,
            'orders_count' => $area->orders_count ?? $area->orders()->count(),
            'center_summary' => number_format((float) $area->latitude, 5).', '.number_format((float) $area->longitude, 5),
        ];
    }
}
