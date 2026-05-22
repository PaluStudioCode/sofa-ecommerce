<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VoucherRequest;
use App\Models\Voucher;
use App\Services\Vouchers\VoucherStatusService;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function __construct(private readonly VoucherStatusService $statuses)
    {
    }

    public function index(Request $request): Response
    {
        $this->statuses->syncAll();

        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif', 'kedaluwarsa', 'kuota_habis'])],
            'discount_type' => ['nullable', Rule::in(['nominal', 'percentage'])],
        ]);

        $vouchers = Voucher::query()
            ->withCount('usages')
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['discount_type'] ?? null, fn ($query, string $type) => $query->where('discount_type', $type))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Voucher $voucher) => $this->payload($voucher));

        return Inertia::render('Admin/Vouchers/Index', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Voucher', 'href' => route('admin.vouchers.index')],
            ],
            'vouchers' => $vouchers,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
                'status' => $filters['status'] ?? '',
                'discount_type' => $filters['discount_type'] ?? '',
            ],
            'statusOptions' => $this->statusOptions(true),
            'discountTypeOptions' => $this->discountTypeOptions(true),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Vouchers/Form', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Voucher', 'href' => route('admin.vouchers.index')],
                ['label' => 'Tambah', 'href' => route('admin.vouchers.create')],
            ],
            'voucher' => null,
            'statusOptions' => $this->statusOptions(false),
            'discountTypeOptions' => $this->discountTypeOptions(false),
        ]);
    }

    public function store(VoucherRequest $request): RedirectResponse
    {
        $data = $this->formData($request);
        $data = $this->statuses->normalize($data);

        $voucher = Voucher::create($data);

        return redirect()->route('admin.vouchers.edit', $voucher)->with('success', 'Voucher disimpan.');
    }

    public function edit(Request $request, Voucher $voucher): Response
    {
        $voucher = $this->statuses->sync($voucher);

        return Inertia::render('Admin/Vouchers/Form', [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Voucher', 'href' => route('admin.vouchers.index')],
                ['label' => 'Edit', 'href' => route('admin.vouchers.edit', $voucher)],
            ],
            'voucher' => $this->payload($voucher),
            'statusOptions' => $this->statusOptions(false),
            'discountTypeOptions' => $this->discountTypeOptions(false),
        ]);
    }

    public function update(VoucherRequest $request, Voucher $voucher): RedirectResponse
    {
        $data = $this->formData($request);
        $data = $this->statuses->normalize($data, $voucher->used_count);

        $voucher->update($data);

        return redirect()->route('admin.vouchers.index')->with('success', 'Voucher diperbarui.');
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        if ($voucher->usages()->exists() || $voucher->orders()->exists()) {
            $voucher->update(['status' => 'nonaktif']);

            return back()->with('success', 'Voucher sudah pernah digunakan, status diubah menjadi nonaktif.');
        }

        $voucher->delete();

        return back()->with('success', 'Voucher dihapus.');
    }

    private function formData(VoucherRequest $request): array
    {
        $data = $request->validated();

        return [
            ...$data,
            'code' => Str::upper(trim($data['code'])),
        ];
    }

    private function payload(Voucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'code' => $voucher->code,
            'name' => $voucher->name,
            'description' => $voucher->description,
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float) $voucher->discount_value,
            'max_discount' => $voucher->max_discount !== null ? (float) $voucher->max_discount : null,
            'minimum_purchase' => (float) $voucher->minimum_purchase,
            'quota' => $voucher->quota,
            'used_count' => $voucher->used_count,
            'per_user_limit' => $voucher->per_user_limit,
            'start_at' => $voucher->start_at?->format('Y-m-d\TH:i'),
            'end_at' => $voucher->end_at?->format('Y-m-d\TH:i'),
            'status' => $voucher->status,
            'usages_count' => $voucher->usages_count ?? $voucher->usages()->count(),
        ];
    }

    private function statusOptions(bool $withAll): array
    {
        $options = collect([
            ['value' => 'aktif', 'label' => 'Aktif'],
            ['value' => 'nonaktif', 'label' => 'Nonaktif'],
            ['value' => 'kedaluwarsa', 'label' => 'Kedaluwarsa'],
            ['value' => 'kuota_habis', 'label' => 'Kuota habis'],
        ]);

        return $withAll
            ? $options->prepend(['value' => '', 'label' => 'Semua status'])->values()->all()
            : $options->values()->all();
    }

    private function discountTypeOptions(bool $withAll): array
    {
        $options = collect([
            ['value' => 'nominal', 'label' => 'Nominal'],
            ['value' => 'percentage', 'label' => 'Persentase'],
        ]);

        return $withAll
            ? $options->prepend(['value' => '', 'label' => 'Semua tipe'])->values()->all()
            : $options->values()->all();
    }
}
