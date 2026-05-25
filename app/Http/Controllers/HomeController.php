<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Voucher;
use App\Services\Vouchers\VoucherStatusService;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, VoucherStatusService $voucherStatuses): Response|RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('dashboard');
        }

        $voucherStatuses->syncAll();

        $sections = collect();

        $featuredProducts = Product::query()
            ->with(['category:id,name', 'variants:id,product_id,price,status,stock,reserved_stock', 'variants.images:id,product_variant_id,file_path,is_primary,sort_order'])
            ->where('status', 'aktif')
            ->where('is_featured', true)
            ->limit(6)
            ->get()
            ->map(fn (Product $product) => $this->productPayload($product));

        $activeVoucher = Voucher::query()
            ->where('status', 'aktif')
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->where(function ($query) {
                $query->whereNull('quota')
                    ->orWhereRaw('(select count(*) from order_voucher_snapshots where order_voucher_snapshots.voucher_id = vouchers.id) < vouchers.quota');
            })
            ->orderBy('end_at')
            ->first();

        return Inertia::render('Home', [
            'sections' => $sections,
            'featuredProducts' => $featuredProducts,
            'activeVoucher' => $activeVoucher ? [
                'code' => $activeVoucher->code,
                'name' => $activeVoucher->name,
                'description' => $activeVoucher->description,
                'discount_type' => $activeVoucher->discount_type,
                'discount_value' => (float) $activeVoucher->discount_value,
                'minimum_purchase' => (float) $activeVoucher->minimum_purchase,
                'end_at' => $activeVoucher->end_at?->toIso8601String(),
            ] : null,
        ]);
    }

    private function productPayload(Product $product): array
    {
        /** @var Collection<int, ProductVariant> $activeVariants */
        $activeVariants = $product->variants->where('status', 'aktif');

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category?->name,
            'image_url' => MediaUrl::fromPath($this->primaryImage($product)?->file_path),
            'min_price' => (float) $activeVariants->min('price'),
            'max_price' => (float) $activeVariants->max('price'),
            'available' => $activeVariants->sum(fn ($variant) => max(0, $variant->stock - $variant->reserved_stock)) > 0,
        ];
    }

    private function primaryImage(Product $product): ?object
    {
        return $product->variants
            ->flatMap(fn ($variant) => $variant->images)
            ->sortBy([
                ['is_primary', 'desc'],
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->first();
    }
}
