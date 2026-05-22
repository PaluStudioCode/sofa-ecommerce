<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductVariantController extends Controller
{
    public function index(): Response
    {
        $variants = ProductVariant::query()
            ->with('product:id,name')
            ->withCount('orderItems')
            ->latest()
            ->get()
            ->map(fn (ProductVariant $variant) => $this->payload($variant));

        return Inertia::render('Admin/Variants/Index', [
            'navigationGroups' => DashboardNavigation::forUser(request()->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Varian dan Stok', 'href' => route('admin.variants.index')],
            ],
            'variants' => $variants,
            'products' => Product::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Product $product) => ['value' => $product->id, 'label' => $product->name])
                ->prepend(['value' => '', 'label' => 'Pilih produk'])
                ->values(),
            'statuses' => [
                ['value' => 'aktif', 'label' => 'Aktif'],
                ['value' => 'nonaktif', 'label' => 'Nonaktif'],
                ['value' => 'stok_habis', 'label' => 'Stok habis'],
            ],
        ]);
    }

    public function store(ProductVariantRequest $request): RedirectResponse
    {
        ProductVariant::create([
            ...$request->validated(),
            'reserved_stock' => 0,
        ]);

        return back()->with('success', 'Varian disimpan.');
    }

    public function update(ProductVariantRequest $request, ProductVariant $variant): RedirectResponse
    {
        $variant->update($request->validated());

        return back()->with('success', 'Varian diperbarui.');
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        if ($variant->orderItems()->exists()) {
            return back()->with('error', 'Varian sudah dipakai dalam transaksi dan tidak dapat dihapus.');
        }

        $variant->delete();

        return back()->with('success', 'Varian dihapus.');
    }

    private function payload(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'product_name' => $variant->product?->name,
            'sku' => $variant->sku,
            'variant_name' => $variant->variant_name,
            'size' => $variant->size,
            'material' => $variant->material,
            'color' => $variant->color,
            'price' => (float) $variant->price,
            'stock' => $variant->stock,
            'reserved_stock' => $variant->reserved_stock,
            'available_stock' => $variant->availableStock(),
            'status' => $variant->status,
            'order_items_count' => $variant->order_items_count,
        ];
    }
}
