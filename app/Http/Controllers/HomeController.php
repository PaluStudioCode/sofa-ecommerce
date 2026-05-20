<?php

namespace App\Http\Controllers;

use App\Models\LandingSection;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $sections = LandingSection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LandingSection $section) => [
                'id' => $section->id,
                'section_key' => $section->section_key,
                'title' => $section->title,
                'subtitle' => $section->subtitle,
                'content' => $section->content,
                'image_url' => $section->image_path ? asset('storage/'.$section->image_path) : null,
                'button_label' => $section->button_label,
                'button_url' => $section->button_url,
                'sort_order' => $section->sort_order,
            ]);

        $featuredProducts = Product::query()
            ->with(['category:id,name', 'variants:id,product_id,price,status,stock,reserved_stock', 'primaryImage:id,product_id,file_path,alt_text'])
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
                $query->whereNull('quota')->orWhereColumn('used_count', '<', 'quota');
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
        /** @var Collection<int, \App\Models\ProductVariant> $activeVariants */
        $activeVariants = $product->variants->where('status', 'aktif');

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category?->name,
            'image_url' => $product->primaryImage?->file_path ? asset('storage/'.$product->primaryImage->file_path) : null,
            'min_price' => (float) $activeVariants->min('price'),
            'max_price' => (float) $activeVariants->max('price'),
            'available' => $activeVariants->sum(fn ($variant) => max(0, $variant->stock - $variant->reserved_stock)) > 0,
        ];
    }
}
