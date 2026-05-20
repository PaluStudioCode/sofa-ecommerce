<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['category:id,name', 'primaryImage:id,product_id,file_path', 'variants:id,product_id,status,price,stock,reserved_stock'])
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
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'navigationGroups' => DashboardNavigation::forUser(request()->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Produk', 'href' => route('admin.products.index')],
                ['label' => 'Tambah', 'href' => route('admin.products.create')],
            ],
            'product' => null,
            'categories' => $this->categoryOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_featured'] = $request->boolean('is_featured');

        $product = Product::create($data);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Produk disimpan.');
    }

    public function show(Product $product): Response
    {
        $product->load(['category:id,name', 'images' => fn ($query) => $query->orderBy('sort_order'), 'variants']);
        $product->loadCount(['variants', 'orderItems']);

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
                    'sku' => $variant->sku,
                    'variant_name' => $variant->variant_name,
                    'price' => (float) $variant->price,
                    'stock' => $variant->stock,
                    'reserved_stock' => $variant->reserved_stock,
                    'status' => $variant->status,
                ]),
                'images' => $product->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => asset('storage/'.$image->file_path),
                    'alt_text' => $image->alt_text,
                    'is_primary' => $image->is_primary,
                    'sort_order' => $image->sort_order,
                ]),
            ],
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'navigationGroups' => DashboardNavigation::forUser(request()->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Produk', 'href' => route('admin.products.index')],
                ['label' => 'Edit', 'href' => route('admin.products.edit', $product)],
            ],
            'product' => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'status' => $product->status,
                'is_featured' => $product->is_featured,
            ],
            'categories' => $this->categoryOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['is_featured'] = $request->boolean('is_featured');

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Produk diperbarui.');
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
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category?->name,
            'status' => $product->status,
            'is_featured' => $product->is_featured,
            'image_url' => $product->primaryImage?->file_path ? asset('storage/'.$product->primaryImage->file_path) : null,
            'variants_count' => $product->variants_count ?? $product->variants->count(),
            'order_items_count' => $product->order_items_count ?? 0,
            'min_price' => (float) $activeVariants->min('price'),
            'max_price' => (float) $activeVariants->max('price'),
            'available_stock' => $activeVariants->sum(fn ($variant) => $variant->availableStock()),
        ];
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
