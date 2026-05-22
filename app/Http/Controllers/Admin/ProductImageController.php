<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\MediaUrl;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductImageController extends Controller
{
    public function index(): Response
    {
        $images = ProductImage::query()
            ->with(['product:id,name', 'variant:id,variant_name,sku'])
            ->orderBy('product_id')
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ProductImage $image) => $this->payload($image));

        return Inertia::render('Admin/ProductImages/Index', [
            'navigationGroups' => DashboardNavigation::forUser(request()->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Gambar Produk', 'href' => route('admin.product-images.index')],
            ],
            'images' => $images,
            'products' => Product::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Product $product) => ['value' => $product->id, 'label' => $product->name])
                ->prepend(['value' => '', 'label' => 'Pilih produk'])
                ->values(),
            'variants' => ProductVariant::query()
                ->with('product:id,name')
                ->orderBy('product_id')
                ->get(['id', 'product_id', 'variant_name', 'sku'])
                ->map(fn (ProductVariant $variant) => [
                    'value' => $variant->id,
                    'product_id' => $variant->product_id,
                    'label' => trim(($variant->product?->name ?? 'Produk').' - '.($variant->variant_name ?: $variant->sku ?: 'Varian')),
                ])
                ->prepend(['value' => '', 'product_id' => null, 'label' => 'Tanpa varian'])
                ->values(),
        ]);
    }

    public function store(ProductImageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_primary'] = $request->boolean('is_primary') || ! ProductImage::where('product_id', $data['product_id'])->exists();
        $data['file_path'] = $request->file('image')->store('products', 'public');

        unset($data['image']);

        DB::transaction(function () use ($data) {
            if ($data['is_primary']) {
                ProductImage::where('product_id', $data['product_id'])->update(['is_primary' => false]);
            }

            ProductImage::create($data);
        });

        return back()->with('success', 'Gambar produk disimpan.');
    }

    public function update(ProductImageRequest $request, ProductImage $productImage): RedirectResponse
    {
        $data = $request->validated();
        $data['is_primary'] = $request->boolean('is_primary');

        if ($request->hasFile('image')) {
            MediaUrl::deleteLocal($productImage->file_path);
            $data['file_path'] = $request->file('image')->store('products', 'public');
        }

        unset($data['image']);

        DB::transaction(function () use ($productImage, $data) {
            if ($data['is_primary']) {
                ProductImage::where('product_id', $data['product_id'])
                    ->whereKeyNot($productImage->id)
                    ->update(['is_primary' => false]);
            }

            $productImage->update($data);
        });

        return back()->with('success', 'Gambar produk diperbarui.');
    }

    public function destroy(ProductImage $productImage): RedirectResponse
    {
        $productId = $productImage->product_id;
        $wasPrimary = $productImage->is_primary;

        MediaUrl::deleteLocal($productImage->file_path);
        $productImage->delete();

        if ($wasPrimary) {
            ProductImage::where('product_id', $productId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first()
                ?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Gambar produk dihapus.');
    }

    public function primary(ProductImage $productImage): RedirectResponse
    {
        ProductImage::where('product_id', $productImage->product_id)->update(['is_primary' => false]);
        $productImage->update(['is_primary' => true]);

        return back()->with('success', 'Gambar utama diperbarui.');
    }

    private function payload(ProductImage $image): array
    {
        return [
            'id' => $image->id,
            'product_id' => $image->product_id,
            'product_name' => $image->product?->name,
            'product_variant_id' => $image->product_variant_id,
            'variant_name' => $image->variant?->variant_name ?: $image->variant?->sku,
            'url' => MediaUrl::fromPath($image->file_path),
            'alt_text' => $image->alt_text,
            'sort_order' => $image->sort_order,
            'is_primary' => $image->is_primary,
        ];
    }
}
