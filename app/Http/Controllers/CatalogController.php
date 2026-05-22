<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $products = Product::query()
            ->with(['category:id,name,slug', 'variants:id,product_id,price,status,stock,reserved_stock', 'primaryImage:id,product_id,file_path,alt_text'])
            ->active()
            ->when($filters['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when($filters['min_price'] ?? null, fn ($query, $price) => $query->whereHas('variants', fn ($variant) => $variant->where('price', '>=', $price)))
            ->when($filters['max_price'] ?? null, fn ($query, $price) => $query->whereHas('variants', fn ($variant) => $variant->where('price', '<=', $price)))
            ->latest()
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Product $product) => $this->productCard($product));

        return Inertia::render('Catalog/Index', [
            'products' => $products,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
                'category' => $filters['category'] ?? '',
                'min_price' => $filters['min_price'] ?? '',
                'max_price' => $filters['max_price'] ?? '',
            ],
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ]);
    }

    public function show(Product $product): Response
    {
        abort_if($product->status !== 'aktif', 404);

        $product->load([
            'category:id,name,slug',
            'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            'variants' => fn ($query) => $query->whereIn('status', ['aktif', 'stok_habis'])->orderBy('price')->orderBy('id'),
        ]);

        return Inertia::render('Catalog/Show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'category' => $product->category?->only(['id', 'name', 'slug']),
                'images' => $product->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => MediaUrl::fromPath($image->file_path),
                    'alt_text' => $image->alt_text ?: $product->name,
                    'is_primary' => $image->is_primary,
                ]),
                'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                    'id' => $variant->id,
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
                    'can_add_to_cart' => $variant->status === 'aktif' && $variant->availableStock() > 0,
                ]),
            ],
        ]);
    }

    private function productCard(Product $product): array
    {
        /** @var Collection<int, ProductVariant> $activeVariants */
        $activeVariants = $product->variants->where('status', 'aktif');

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category?->name,
            'image_url' => MediaUrl::fromPath($product->primaryImage?->file_path),
            'min_price' => (float) $activeVariants->min('price'),
            'max_price' => (float) $activeVariants->max('price'),
            'available' => $activeVariants->sum(fn (ProductVariant $variant) => $variant->availableStock()) > 0,
        ];
    }
}
