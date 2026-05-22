<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\Reports\BusinessReportService;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly BusinessReportService $reports)
    {
    }

    public function sales(Request $request): Response
    {
        $period = $this->period($request);

        return $this->render($request, 'Owner/Reports/Sales', 'Penjualan', [
            'summary' => $this->reports->salesSummary($period),
            'rows' => $this->reports->salesRows($period),
        ], $period);
    }

    public function products(Request $request): Response
    {
        $period = $this->period($request);

        return $this->render($request, 'Owner/Reports/Products', 'Produk Terjual', [
            'summary' => $this->reports->salesSummary($period),
            'rows' => $this->reports->productRows($period),
        ], $period);
    }

    public function vouchers(Request $request): Response
    {
        $period = $this->period($request);

        return $this->render($request, 'Owner/Reports/Vouchers', 'Voucher', [
            'summary' => $this->reports->salesSummary($period),
            'rows' => $this->reports->voucherRows($period),
        ], $period);
    }

    public function shipping(Request $request): Response
    {
        $period = $this->period($request);

        return $this->render($request, 'Owner/Reports/Shipping', 'Biaya Pengiriman', [
            'summary' => $this->reports->salesSummary($period),
            'rows' => $this->reports->shippingRows($period),
        ], $period);
    }

    private function period(Request $request): array
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        return $this->reports->period($filters);
    }

    private function render(Request $request, string $component, string $label, array $props, array $period): Response
    {
        return Inertia::render($component, [
            'navigationGroups' => DashboardNavigation::forUser($request->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => $label, 'href' => url()->current()],
            ],
            'period' => $period,
            ...$props,
        ]);
    }
}
