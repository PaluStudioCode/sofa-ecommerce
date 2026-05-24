<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $items = CartItem::query()
            ->with([
                'product.category:id,name',
                'variant:id,product_id,sku,variant_name,size,material,color,price,stock,reserved_stock,status',
                'variant.images:id,product_variant_id,file_path,is_primary,sort_order',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (CartItem $item) => $this->payload($item));

        $canCheckout = $items->isNotEmpty() && $items->every(fn (array $item) => $item['is_valid']);

        return Inertia::render('Cart/Index', [
            'items' => $items,
            'summary' => [
                'items_count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'subtotal' => $items->sum('subtotal'),
                'can_checkout' => $canCheckout,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::query()
            ->with('product:id,status')
            ->whereKey($data['product_variant_id'])
            ->where('product_id', $data['product_id'])
            ->firstOrFail();

        $this->assertPurchasable($variant, $data['quantity']);

        $item = CartItem::firstOrNew([
            'user_id' => $request->user()->id,
            'product_variant_id' => $variant->id,
        ]);

        $newQuantity = ($item->exists ? $item->quantity : 0) + $data['quantity'];
        $this->assertPurchasable($variant, $newQuantity);

        $item->fill([
            'product_id' => $variant->product_id,
            'quantity' => $newQuantity,
        ])->save();

        return redirect()->route('cart.index')->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCartItemAccess($request, $cartItem);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartItem->load('variant.product:id,status');
        $this->assertPurchasable($cartItem->variant, $data['quantity']);

        $cartItem->update(['quantity' => $data['quantity']]);

        return back()->with('success', 'Jumlah keranjang diperbarui.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCartItemAccess($request, $cartItem);

        $cartItem->delete();

        return back()->with('success', 'Item keranjang dihapus.');
    }

    private function payload(CartItem $item): array
    {
        $variant = $item->variant;
        $product = $item->product;
        $availableStock = $variant?->availableStock() ?? 0;
        $warning = $this->warningFor($item, $availableStock);

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'product_name' => $product?->name,
            'product_slug' => $product?->slug,
            'category' => $product?->category?->name,
            'image_url' => MediaUrl::fromPath($this->primaryImage($variant)?->file_path),
            'variant_name' => $variant?->variant_name ?: $variant?->sku,
            'sku' => $variant?->sku,
            'size' => $variant?->size,
            'material' => $variant?->material,
            'color' => $variant?->color,
            'unit_price' => (float) ($variant?->price ?? 0),
            'quantity' => $item->quantity,
            'available_stock' => $availableStock,
            'subtotal' => (float) ($variant?->price ?? 0) * $item->quantity,
            'product_status' => $product?->status,
            'variant_status' => $variant?->status,
            'is_valid' => $warning === null,
            'warning' => $warning,
        ];
    }

    private function primaryImage(?ProductVariant $variant): ?object
    {
        return $variant?->images
            ->sortBy([
                ['is_primary', 'desc'],
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->first();
    }

    private function warningFor(CartItem $item, int $availableStock): ?string
    {
        if ($item->product?->status !== 'aktif') {
            return 'Produk sudah tidak aktif dan tidak bisa checkout.';
        }

        if ($item->variant?->status !== 'aktif') {
            return 'Varian sudah tidak aktif atau stok habis.';
        }

        if ($availableStock < 1) {
            return 'Stok tersedia varian sudah habis.';
        }

        if ($item->quantity > $availableStock) {
            return "Jumlah melebihi stok tersedia. Stok saat ini {$availableStock}.";
        }

        return null;
    }

    private function assertPurchasable(ProductVariant $variant, int $quantity): void
    {
        if ($variant->product?->status !== 'aktif') {
            throw ValidationException::withMessages(['product_id' => 'Produk tidak aktif.']);
        }

        if ($variant->status !== 'aktif') {
            throw ValidationException::withMessages(['product_variant_id' => 'Varian tidak aktif atau stok habis.']);
        }

        if ($quantity > $variant->availableStock()) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah melebihi stok tersedia.']);
        }
    }

    private function authorizeCartItemAccess(Request $request, CartItem $cartItem): void
    {
        abort_unless($cartItem->user_id === $request->user()->id, 404);
    }
}
