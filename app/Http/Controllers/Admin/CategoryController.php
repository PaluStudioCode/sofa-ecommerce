<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Support\Navigation\DashboardNavigation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'products_count' => $category->products_count,
            ]);

        return Inertia::render('Admin/Categories/Index', [
            'navigationGroups' => DashboardNavigation::forUser(request()->user()),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Kategori', 'href' => route('admin.categories.index')],
            ],
            'categories' => $categories,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        Category::create($data);

        return back()->with('success', 'Kategori disimpan.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $usedInOrders = $category->products()
            ->whereHas('orderItems')
            ->exists();

        if ($usedInOrders) {
            return back()->with('error', 'Kategori sudah dipakai dalam transaksi dan tidak dapat dihapus.');
        }

        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
