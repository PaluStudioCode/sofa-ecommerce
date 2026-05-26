<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\MediaUrl;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ]);

        $products = Product::query()
            ->with(['category:id,name', 'primaryImage:id,product_variant_id,file_path,alt_text', 'variants:id,product_id,status,price,stock,reserved_stock', 'variants.images:id,product_variant_id,file_path,is_primary,sort_order'])
            ->withCount(['variants', 'orderItems'])
            ->when($filters['keyword'] ?? null, fn ($query, $keyword) => $query->where('name', 'like', "%{$keyword}%"))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => $this->payload($product));

        return Inertia::render('Admin/Products/Index', [
            'navigationGroups' => DashboardNavigation::forUser(request()->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Produk', 'href' => route('admin.products.index')],
            ],
            'products' => $products,
            'filters' => [
                'keyword' => $filters['keyword'] ?? '',
                'category' => $filters['category'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'categories' => $this->categoryOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.products.index');
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_featured'] = $request->boolean('is_featured');

        if (($data['status'] ?? null) === 'aktif') {
            throw ValidationException::withMessages([
                'status' => 'Produk baru dibuat sebagai nonaktif dulu. Tambahkan varian aktif, stok, gambar, dan thumbnail sebelum dipublish.',
            ]);
        }

        $product = Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Produk disimpan.');
    }

    public function show(Product $product): Response
    {
        $product->load([
            'category:id,name',
            'primaryImage:id,product_variant_id,file_path,alt_text',
            'variants' => fn ($query) => $query
                ->with(['images' => fn ($imageQuery) => $imageQuery->with('variant:id,product_id,variant_name,sku')->orderBy('sort_order')->orderBy('id')])
                ->withCount('orderItems')
                ->orderBy('id'),
        ]);
        $product->loadCount(['variants', 'orderItems']);

        $images = $product->variants->flatMap(fn ($variant) => $variant->images);

        return Inertia::render('Admin/Products/Show', [
            'navigationGroups' => DashboardNavigation::forUser(request()->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Produk', 'href' => route('admin.products.index')],
                ['label' => $product->name, 'href' => route('admin.products.show', $product)],
            ],
            'product' => [
                ...$this->payload($product),
                'description' => $product->description,
                'variants' => $product->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
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
                ]),
                'images' => $images->map(fn ($image) => [
                    'id' => $image->id,
                    'product_id' => $image->variant?->product_id,
                    'product_variant_id' => $image->product_variant_id,
                    'variant_name' => $image->variant?->variant_name ?: $image->variant?->sku,
                    'url' => MediaUrl::fromPath($image->file_path),
                    'alt_text' => $image->alt_text,
                    'is_primary' => $image->is_primary,
                    'is_product_thumbnail' => $product->primary_image_id === $image->id,
                    'sort_order' => $image->sort_order,
                ]),
            ],
            'categories' => $this->categoryOptions(),
            'statuses' => $this->statusOptions(),
            'variantStatuses' => [
                ['value' => 'aktif', 'label' => 'Aktif'],
                ['value' => 'nonaktif', 'label' => 'Nonaktif'],
                ['value' => 'stok_habis', 'label' => 'Stok habis'],
            ],
        ]);
    }

    public function edit(Product $product): RedirectResponse
    {
        return redirect()->route('admin.products.index');
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['is_featured'] = $request->boolean('is_featured');

        $product->fill($data);

        if ($product->status === 'aktif' && ! $product->isPublishReady()) {
            throw ValidationException::withMessages([
                'status' => 'Produk belum siap tampil: '.implode(' ', $product->publishBlockers()),
            ]);
        }

        $product->save();

        return back()->with('success', 'Produk diperbarui.');
    }

    public function thumbnail(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'image_id' => ['required', 'integer', 'exists:product_images,id'],
        ], [], [
            'image_id' => 'thumbnail produk',
        ]);

        $image = ProductImage::query()
            ->whereKey($data['image_id'])
            ->whereHas('variant', fn ($query) => $query
                ->where('product_id', $product->id)
                ->where('status', 'aktif')
                ->whereColumn('stock', '>', 'reserved_stock'))
            ->first();

        if (! $image) {
            throw ValidationException::withMessages([
                'image_id' => 'Thumbnail harus berasal dari varian aktif produk ini yang stoknya tersedia.',
            ]);
        }

        $product->update(['primary_image_id' => $image->id]);

        return back()->with('success', 'Thumbnail produk diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return back()->with('error', 'Produk sudah dipakai dalam transaksi dan tidak dapat dihapus.');
        }

        $product->delete();

        return back()->with('success', 'Produk dihapus.');
    }

    private function payload(Product $product): array
    {
        $activeVariants = $product->variants->where('status', 'aktif');

        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'category' => $product->category?->name,
            'status' => $product->status,
            'is_featured' => $product->is_featured,
            'primary_image_id' => $product->primary_image_id,
            'is_publish_ready' => $product->isPublishReady(),
            'publish_blockers' => $product->publishBlockers(),
            'image_url' => MediaUrl::fromPath($this->primaryImage($product)?->file_path),
            'variants_count' => $product->variants_count ?? $product->variants->count(),
            'order_items_count' => $product->order_items_count ?? 0,
            'min_price' => (float) $activeVariants->min('price'),
            'max_price' => (float) $activeVariants->max('price'),
            'available_stock' => $activeVariants->sum(fn ($variant) => $variant->availableStock()),
        ];
    }

    private function primaryImage(Product $product): ?object
    {
        $selectedImage = $product->relationLoaded('primaryImage')
            ? $product->primaryImage
            : $product->primaryImage()->first();

        if ($selectedImage) {
            return $selectedImage;
        }

        return $product->variants
            ->flatMap(fn ($variant) => $variant->images)
            ->sortBy([
                ['is_primary', 'desc'],
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->first();
    }

    private function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => ['value' => $category->id, 'label' => $category->name])
            ->prepend(['value' => '', 'label' => 'Pilih kategori'])
            ->values()
            ->all();
    }

    private function statusOptions(): array
    {
        return [
            ['value' => 'aktif', 'label' => 'Aktif'],
            ['value' => 'nonaktif', 'label' => 'Nonaktif'],
        ];
    }
}
