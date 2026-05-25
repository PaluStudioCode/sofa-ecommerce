<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Voucher;
use App\Services\Notifications\WhatsAppNotificationService;
use App\Services\Payments\PaymentAttemptService;
use App\Services\Shipping\RouteDistanceService;
use App\Services\Vouchers\VoucherStatusService;
use App\Support\GeoDistance;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(private readonly RouteDistanceService $routeDistance) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $items = $this->cartItems($request);

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong. Pilih produk terlebih dahulu.');
        }

        $location = $this->userShippingLocation($request->user()) ?? $request->session()->get('checkout.location');
        $voucherCode = $request->session()->get('checkout.voucher_code', '');

        $summary = $items->isEmpty()
            ? $this->emptySummary()
            : $this->quote($items, $request->user()->id, $location, $voucherCode, false);

        return Inertia::render('Checkout/Index', [
            'items' => $items->map(fn (CartItem $item) => $this->cartPayload($item)),
            'summary' => $summary,
            'location' => $location,
            'voucherCode' => $voucherCode,
        ]);
    }

    public function resolveLocation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'formatted_address' => ['required', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $location = $this->locationFromRequest($data);

        $request->session()->put('checkout.location', $location);

        $redirect = redirect()->route('checkout.index');

        if ($request->boolean('auto_update')) {
            return $redirect;
        }

        return $redirect->with('success', 'Lokasi pengiriman dipilih.');
    }

    public function quoteRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'voucher_code' => ['nullable', 'string', 'max:100'],
        ]);

        $items = $this->cartItems($request);

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong. Pilih produk terlebih dahulu.');
        }

        $location = $this->userShippingLocation($request->user()) ?? $request->session()->get('checkout.location');

        if (! $location) {
            throw ValidationException::withMessages([
                'location' => 'Atur alamat pengiriman terlebih dahulu.',
            ]);
        }

        if (! $this->hasCompleteShippingContact($location)) {
            throw ValidationException::withMessages([
                'location' => 'Lengkapi nama penerima, nomor telepon, dan detail alamat pengiriman.',
            ]);
        }

        $voucherCode = Str::upper(trim((string) ($data['voucher_code'] ?? '')));
        $this->quote($items, $request->user()->id, $location, $voucherCode, true);
        $request->session()->put('checkout.voucher_code', $voucherCode);

        return redirect()->route('checkout.index')->with('success', 'Ringkasan checkout diperbarui.');
    }

    public function store(Request $request, PaymentAttemptService $payments, WhatsAppNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'voucher_code' => ['nullable', 'string', 'max:100'],
        ]);

        $location = $this->userShippingLocation($request->user()) ?? $request->session()->get('checkout.location');

        if (! $location) {
            throw ValidationException::withMessages([
                'location' => 'Atur alamat pengiriman terlebih dahulu.',
            ]);
        }

        $order = DB::transaction(function () use ($request, $data, $location) {
            $cartItems = CartItem::query()
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Keranjang kosong. Pilih produk terlebih dahulu.',
                ]);
            }

            $variantIds = $cartItems->pluck('product_variant_id')->all();
            ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->lockForUpdate()
                ->get();

            $cartItems->load(['variant.product.category']);

            $voucherCode = Str::upper(trim((string) ($data['voucher_code'] ?? $request->session()->get('checkout.voucher_code', ''))));
            $quote = $this->quote($cartItems, $request->user()->id, $location, $voucherCode, true, true);
            $address = $this->ensureUserAddress($request->user(), $location);

            $order = Order::create([
                'order_number' => $this->nextOrderNumber(),
                'user_id' => $request->user()->id,
                'order_status' => 'menunggu_pembayaran',
            ]);

            $order->total()->create([
                'subtotal_amount' => $quote['subtotal'],
                'discount_amount' => $quote['discount_amount'],
                'shipping_cost' => $quote['shipping_cost'],
                'total_amount' => $quote['total'],
            ]);

            $order->address()->create([
                'user_address_id' => $address->id,
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'detail' => $address->detail,
                'formatted_address' => $address->formatted_address,
                'city' => $address->city,
                'district' => $address->district,
                'postal_code' => $address->postal_code,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
            ]);

            if ($quote['voucher']) {
                $order->voucherSnapshot()->create([
                    'voucher_id' => $quote['voucher']['id'],
                    'voucher_code' => $quote['voucher']['code'],
                    'voucher_name' => $quote['voucher']['name'],
                ]);
            }

            $order->shippingSnapshot()->create([
                'shipping_setting_id' => $quote['store']['id'],
                'origin_name' => $quote['store']['name'],
            ]);

            foreach ($cartItems as $item) {
                $variant = $item->variant;

                $order->items()->create([
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->variant_name,
                    'variant_sku' => $variant->sku,
                    'variant_size' => $variant->size,
                    'variant_material' => $variant->material,
                    'variant_color' => $variant->color,
                    'product_price' => $variant->price,
                    'quantity' => $item->quantity,
                    'subtotal' => (float) $variant->price * $item->quantity,
                ]);

                $variant->increment('reserved_stock', $item->quantity);
            }

            CartItem::where('user_id', $request->user()->id)->delete();

            return $order;
        });

        $request->session()->forget(['checkout.location', 'checkout.voucher_code']);
        $notifications->sendOrderEvent($order, 'order_created');
        $payments->createAttempt($order);

        return redirect()
            ->route('orders.show', ['order' => $order->id, 'new_order' => 1])
            ->with('success', 'Pesanan dibuat. Silakan lanjutkan pembayaran.');
    }

    private function cartItems(Request $request): Collection
    {
        return CartItem::query()
            ->with([
                'variant:id,product_id,sku,variant_name,size,material,color,price,stock,reserved_stock,status',
                'variant.product:id,category_id,name,slug,status',
                'variant.product.category:id,name',
                'variant.images:id,product_variant_id,file_path,is_primary,sort_order',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();
    }

    private function cartPayload(CartItem $item): array
    {
        return [
            'id' => $item->id,
            'product_name' => $item->variant->product->name,
            'product_slug' => $item->variant->product->slug,
            'category' => $item->variant->product->category?->name,
            'image_url' => MediaUrl::fromPath($this->primaryImage($item->variant)?->file_path),
            'variant_name' => $item->variant->variant_name ?: $item->variant->sku,
            'specification' => collect([$item->variant->size, $item->variant->material, $item->variant->color])->filter()->join(' / '),
            'unit_price' => (float) $item->variant->price,
            'quantity' => $item->quantity,
            'subtotal' => (float) $item->variant->price * $item->quantity,
        ];
    }

    private function primaryImage(ProductVariant $variant): ?object
    {
        return $variant->images
            ->sortBy([
                ['is_primary', 'desc'],
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->first();
    }

    private function quote(Collection $items, int $userId, ?array $location, string $voucherCode, bool $strict, bool $lockVoucher = false): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $this->assertCartItemValid($item);
            $subtotal += (float) $item->variant->price * $item->quantity;
        }

        $shippingRule = $location ? $this->matchingShippingRule((float) $location['latitude'], (float) $location['longitude']) : null;

        if ($strict && ! $shippingRule) {
            throw ValidationException::withMessages([
                'location' => 'Alamat belum masuk radius layanan pengiriman atau jarak jalan belum dapat dihitung.',
            ]);
        }

        if ($strict && ! $this->hasCompleteShippingContact($location)) {
            throw ValidationException::withMessages([
                'location' => 'Lengkapi nama penerima, nomor telepon, dan detail alamat pengiriman.',
            ]);
        }

        $voucher = $voucherCode !== ''
            ? $this->validVoucher($voucherCode, $subtotal, $userId, $lockVoucher)
            : null;
        $discount = $voucher ? $this->discountAmount($voucher, $subtotal) : 0.0;
        $shippingCost = $shippingRule ? $shippingRule['shipping_cost'] : 0.0;
        $shippingSetting = $shippingRule['shipping_setting'] ?? null;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => min($discount, $subtotal),
            'shipping_cost' => $shippingCost,
            'total' => max(0, $subtotal - min($discount, $subtotal)) + $shippingCost,
            'store' => $shippingSetting ? [
                'id' => $shippingSetting->id,
                'name' => $shippingSetting->origin_name,
                'origin_address' => $shippingSetting->origin_address,
                'origin_latitude' => (float) $shippingSetting->origin_latitude,
                'origin_longitude' => (float) $shippingSetting->origin_longitude,
                'radius_km' => (float) $shippingSetting->radius_km,
                'shipping_cost_per_km' => (float) $shippingSetting->shipping_cost_per_km,
                'distance_km' => $shippingRule['distance_km'],
                'billable_distance_km' => $shippingRule['billable_distance_km'],
                'duration_seconds' => $shippingRule['duration_seconds'],
                'distance_provider' => $shippingRule['distance_provider'],
                'route_geometry' => $shippingRule['route_geometry'],
            ] : null,
            'voucher' => $voucher ? [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->name,
                'discount_type' => $voucher->discount_type,
                'discount_value' => (float) $voucher->discount_value,
                'max_discount' => $voucher->max_discount !== null ? (float) $voucher->max_discount : null,
                'minimum_purchase' => (float) $voucher->minimum_purchase,
            ] : null,
            'can_submit' => $items->isNotEmpty() && $location !== null && $shippingSetting !== null && $this->hasCompleteShippingContact($location),
        ];
    }

    private function hasCompleteShippingContact(?array $location): bool
    {
        return filled($location['recipient_name'] ?? null)
            && filled($location['phone'] ?? null)
            && filled($location['detail'] ?? null);
    }

    private function assertCartItemValid(CartItem $item): void
    {
        if ($item->variant->product->status !== 'aktif') {
            throw ValidationException::withMessages(['cart' => "{$item->variant->product->name} sudah tidak aktif."]);
        }

        if ($item->variant->status !== 'aktif') {
            throw ValidationException::withMessages(['cart' => "Varian {$item->variant->product->name} sudah tidak aktif."]);
        }

        if ($item->quantity > $item->variant->availableStock()) {
            throw ValidationException::withMessages(['cart' => "Stok {$item->variant->product->name} tidak mencukupi."]);
        }
    }

    private function matchingShippingRule(float $latitude, float $longitude): ?array
    {
        $shippingSetting = ShippingSetting::query()
            ->where('is_active', true)
            ->latest()
            ->first();

        if (! $shippingSetting) {
            return null;
        }

        $straightDistanceMeters = GeoDistance::haversineMeters(
            (float) $shippingSetting->origin_latitude,
            (float) $shippingSetting->origin_longitude,
            $latitude,
            $longitude
        );

        $radiusMeters = (float) $shippingSetting->radius_km * 1000;

        if ($straightDistanceMeters > $radiusMeters) {
            return null;
        }

        try {
            $routeDistance = $this->routeDistance->drivingDistance(
                (float) $shippingSetting->origin_latitude,
                (float) $shippingSetting->origin_longitude,
                $latitude,
                $longitude
            );
        } catch (Throwable $exception) {
            Log::warning('Shipping route distance lookup failed.', [
                'shipping_setting_id' => $shippingSetting->id,
                'origin_latitude' => (float) $shippingSetting->origin_latitude,
                'origin_longitude' => (float) $shippingSetting->origin_longitude,
                'destination_latitude' => $latitude,
                'destination_longitude' => $longitude,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);

            return null;
        }

        $distanceMeters = (float) $routeDistance['distance_meters'];

        if ($distanceMeters > $radiusMeters) {
            return null;
        }

        $distanceKm = $distanceMeters / 1000;
        $billableDistanceKm = max(1, (int) round($distanceKm, 0, PHP_ROUND_HALF_UP));

        return [
            'shipping_setting' => $shippingSetting,
            'distance_km' => round($distanceKm, 2),
            'billable_distance_km' => $billableDistanceKm,
            'duration_seconds' => $routeDistance['duration_seconds'],
            'distance_provider' => $routeDistance['provider'],
            'route_geometry' => $routeDistance['route_geometry'] ?? [],
            'shipping_cost' => $billableDistanceKm * (float) $shippingSetting->shipping_cost_per_km,
        ];
    }

    private function validVoucher(string $code, float $subtotal, int $userId, bool $lock): Voucher
    {
        $query = Voucher::query()->where('code', $code);

        if ($lock) {
            $query->lockForUpdate();
        }

        $voucher = $query->first();

        if ($voucher) {
            $voucher = app(VoucherStatusService::class)->sync($voucher);
        }

        if (! $voucher || $voucher->status !== 'aktif') {
            throw ValidationException::withMessages(['voucher_code' => 'Voucher tidak valid.']);
        }

        if ($voucher->start_at->isFuture() || $voucher->end_at->isPast()) {
            throw ValidationException::withMessages(['voucher_code' => 'Voucher sudah berakhir atau belum aktif.']);
        }

        if ($voucher->quota !== null && $voucher->orders()->count() >= $voucher->quota) {
            throw ValidationException::withMessages(['voucher_code' => 'Kuota voucher sudah habis.']);
        }

        if ($subtotal < (float) $voucher->minimum_purchase) {
            throw ValidationException::withMessages(['voucher_code' => 'Subtotal belum memenuhi minimum pembelian voucher.']);
        }

        if ($voucher->per_user_limit !== null) {
            $usedByUser = Order::query()
                ->where('user_id', $userId)
                ->whereHas('voucherSnapshot', fn ($query) => $query->where('voucher_id', $voucher->id))
                ->count();

            if ($usedByUser >= $voucher->per_user_limit) {
                throw ValidationException::withMessages(['voucher_code' => 'Batas penggunaan voucher untuk akun ini sudah tercapai.']);
            }
        }

        return $voucher;
    }

    private function discountAmount(Voucher $voucher, float $subtotal): float
    {
        if ($voucher->discount_type === 'nominal') {
            return (float) $voucher->discount_value;
        }

        $discount = $subtotal * ((float) $voucher->discount_value / 100);

        return $voucher->max_discount !== null
            ? min($discount, (float) $voucher->max_discount)
            : $discount;
    }

    private function locationFromRequest(array $data): array
    {
        return [
            'recipient_name' => $data['recipient_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'detail' => $data['detail'] ?? null,
            'formatted_address' => trim((string) $data['formatted_address']),
            'city' => filled($data['city'] ?? null) ? trim((string) $data['city']) : null,
            'district' => filled($data['district'] ?? null) ? trim((string) $data['district']) : null,
            'postal_code' => filled($data['postal_code'] ?? null) ? trim((string) $data['postal_code']) : null,
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
        ];
    }

    private function userShippingLocation(User $user): ?array
    {
        $address = $user->relationLoaded('defaultAddress')
            ? $user->defaultAddress
            : $user->defaultAddress()->first();

        if (! $address || ! $address->formatted_address || $address->latitude === null || $address->longitude === null) {
            return null;
        }

        return [
            'user_address_id' => $address->id,
            'recipient_name' => $address->recipient_name ?: $user->name,
            'phone' => $address->phone ?: $user->phone,
            'detail' => $address->detail,
            'formatted_address' => $address->formatted_address,
            'city' => $address->city,
            'district' => $address->district,
            'postal_code' => $address->postal_code,
            'latitude' => (float) $address->latitude,
            'longitude' => (float) $address->longitude,
        ];
    }

    private function ensureUserAddress(User $user, array $location): UserAddress
    {
        $addressId = $location['user_address_id'] ?? $location['id'] ?? null;

        if ($addressId) {
            $address = $user->addresses()->whereKey($addressId)->first();

            if ($address) {
                return $address;
            }
        }

        $payload = [
            'recipient_name' => trim((string) ($location['recipient_name'] ?: $user->name)),
            'phone' => trim((string) ($location['phone'] ?: $user->phone)),
            'detail' => trim((string) $location['detail']),
            'formatted_address' => trim((string) $location['formatted_address']),
            'city' => filled($location['city'] ?? null) ? trim((string) $location['city']) : null,
            'district' => filled($location['district'] ?? null) ? trim((string) $location['district']) : null,
            'postal_code' => filled($location['postal_code'] ?? null) ? trim((string) $location['postal_code']) : null,
            'latitude' => (float) $location['latitude'],
            'longitude' => (float) $location['longitude'],
            'is_default' => true,
        ];

        $address = $user->defaultAddress()->first();

        if ($address && ! $address->orders()->exists()) {
            $address->update($payload);
        } else {
            $address = $user->addresses()->create($payload);
        }

        $user->addresses()
            ->whereKeyNot($address->id)
            ->update(['is_default' => false]);

        return $address->fresh();
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    private function emptySummary(): array
    {
        return [
            'subtotal' => 0,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total' => 0,
            'store' => null,
            'voucher' => null,
            'can_submit' => false,
        ];
    }
}
