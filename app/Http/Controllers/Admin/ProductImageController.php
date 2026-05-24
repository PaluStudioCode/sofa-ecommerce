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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductImageController extends Controller
{
    public function index(): Response
    {
        $images = ProductImage::query()
            ->with(['variant.product:id,name', 'variant:id,product_id,variant_name,sku'])
            ->orderBy('product_variant_id')
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
                ->values(),
        ]);
    }

    public function store(ProductImageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $files = $request->file('images') ?: [$request->file('image')];
        $usesPreviewOrder = $request->hasFile('images');
        $makeFirstPrimary = $usesPreviewOrder || $request->boolean('is_primary') || ! ProductImage::where('product_variant_id', $data['product_variant_id'])->exists();
        $baseSortOrder = $data['sort_order'] ?? $this->nextSortOrder($data['product_variant_id']);

        unset($data['image']);
        unset($data['images']);
        unset($data['product_id']);
        unset($data['sort_order']);
        unset($data['is_primary']);

        DB::transaction(function () use ($data, $files, $makeFirstPrimary, $baseSortOrder) {
            if ($makeFirstPrimary) {
                ProductImage::where('product_variant_id', $data['product_variant_id'])->update(['is_primary' => false]);
            }

            foreach ($files as $index => $file) {
                ProductImage::create([
                    ...$data,
                    'file_path' => $file->store('products', 'public'),
                    'sort_order' => $baseSortOrder + $index,
                    'is_primary' => $makeFirstPrimary && $index === 0,
                ]);
            }
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
        unset($data['images']);
        unset($data['product_id']);

        DB::transaction(function () use ($productImage, $data) {
            if ($data['is_primary']) {
                ProductImage::where('product_variant_id', $data['product_variant_id'])
                    ->whereKeyNot($productImage->id)
                    ->update(['is_primary' => false]);
            }

            $productImage->update($data);
        });

        return back()->with('success', 'Gambar produk diperbarui.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'image_ids' => ['required', 'array', 'min:1'],
            'image_ids.*' => ['integer', 'exists:product_images,id'],
        ]);

        $belongsToProduct = ProductVariant::query()
            ->whereKey($data['product_variant_id'])
            ->where('product_id', $data['product_id'])
            ->exists();

        if (! $belongsToProduct) {
            return back()->with('error', 'Varian harus berasal dari produk yang sama.');
        }

        $images = ProductImage::query()
            ->where('product_variant_id', $data['product_variant_id'])
            ->whereIn('id', $data['image_ids'])
            ->get()
            ->keyBy('id');

        if ($images->count() !== count($data['image_ids']) || $images->count() !== count(array_unique($data['image_ids']))) {
            return back()->with('error', 'Urutan gambar tidak valid.');
        }

        DB::transaction(function () use ($data, $images) {
            foreach (array_values($data['image_ids']) as $index => $imageId) {
                $images[$imageId]->update(['sort_order' => $index]);
            }
        });

        return back()->with('success', 'Urutan gambar diperbarui.');
    }

    public function destroy(ProductImage $productImage): RedirectResponse
    {
        $variantId = $productImage->product_variant_id;
        $wasPrimary = $productImage->is_primary;

        MediaUrl::deleteLocal($productImage->file_path);
        $productImage->delete();

        if ($wasPrimary) {
            ProductImage::where('product_variant_id', $variantId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first()
                ?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Gambar produk dihapus.');
    }

    public function primary(ProductImage $productImage): RedirectResponse
    {
        ProductImage::where('product_variant_id', $productImage->product_variant_id)->update(['is_primary' => false]);
        $productImage->update(['is_primary' => true]);

        return back()->with('success', 'Gambar utama diperbarui.');
    }

    private function payload(ProductImage $image): array
    {
        return [
            'id' => $image->id,
            'product_id' => $image->variant?->product_id,
            'product_name' => $image->variant?->product?->name,
            'product_variant_id' => $image->product_variant_id,
            'variant_name' => $image->variant?->variant_name ?: $image->variant?->sku,
            'url' => MediaUrl::fromPath($image->file_path),
            'alt_text' => $image->alt_text,
            'sort_order' => $image->sort_order,
            'is_primary' => $image->is_primary,
        ];
    }

    private function nextSortOrder(int $variantId): int
    {
        $maxSortOrder = ProductImage::query()
            ->where('product_variant_id', $variantId)
            ->max('sort_order');

        return $maxSortOrder === null ? 0 : $maxSortOrder + 1;
    }
}
