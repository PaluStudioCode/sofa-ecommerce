<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingArea;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\GoogleMaps\GoogleMapsClient;
use App\Services\Midtrans\MidtransPaymentGateway;
use App\Services\Payments\PaymentAttemptService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function index(Request $request, GoogleMapsClient $maps, MidtransPaymentGateway $midtrans): Response|RedirectResponse
    {
        $createdOrder = $this->createdOrder($request);
        $items = $this->cartItems($request);

        if ($items->isEmpty() && ! $createdOrder) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kosong. Pilih produk terlebih dahulu.');
        }

        $location = $request->session()->get('checkout.location');
        $voucherCode = $request->session()->get('checkout.voucher_code', '');

        $summary = $items->isEmpty()
            ? $this->emptySummary()
            : $this->quote($items, $request->user()->id, $location, $voucherCode, false);

        return Inertia::render('Checkout/Index', [
            'items' => $items->map(fn (CartItem $item) => $this->cartPayload($item)),
            'summary' => $summary,
            'location' => $location,
            'voucherCode' => $voucherCode,
            'googleMaps' => $maps->browserConfig(),
            'midtrans' => $midtrans->clientConfig(),
            'createdOrder' => $createdOrder,
        ]);
    }

    public function resolveLocation(Request $request, GoogleMapsClient $maps): RedirectResponse
    {
        $data = $request->validate([
            'place_id' => ['required', 'string', 'max:255'],
        ]);

        $geocode = $maps->geocodePlace($data['place_id']);
        $location = $this->locationFromGeocode($geocode);

        if (! $location) {
            throw ValidationException::withMessages([
                'place_id' => 'Lokasi tidak memiliki alamat atau koordinat yang valid.',
            ]);
        }

        $request->session()->put('checkout.location', $location);

        return redirect()->route('checkout.index')->with('success', 'Lokasi pengiriman dipilih.');
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

        $location = $request->session()->get('checkout.location');

        if (! $location) {
            throw ValidationException::withMessages([
                'location' => 'Pilih alamat pengiriman dari Google Maps terlebih dahulu.',
            ]);
        }

        $voucherCode = Str::upper(trim((string) ($data['voucher_code'] ?? '')));
        $this->quote($items, $request->user()->id, $location, $voucherCode, true);
        $request->session()->put('checkout.voucher_code', $voucherCode);

        return redirect()->route('checkout.index')->with('success', 'Ringkasan checkout diperbarui.');
    }

    public function store(Request $request, PaymentAttemptService $payments): RedirectResponse
    {
        $data = $request->validate([
            'customer_phone' => ['required', 'string', 'max:30'],
            'shipping_note' => ['nullable', 'string', 'max:1000'],
            'voucher_code' => ['nullable', 'string', 'max:100'],
        ]);

        $location = $request->session()->get('checkout.location');

        if (! $location) {
            throw ValidationException::withMessages([
                'location' => 'Pilih alamat pengiriman dari Google Maps terlebih dahulu.',
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

            $cartItems->load(['product.category', 'variant']);

            $voucherCode = Str::upper(trim((string) ($data['voucher_code'] ?? $request->session()->get('checkout.voucher_code', ''))));
            $quote = $this->quote($cartItems, $request->user()->id, $location, $voucherCode, true, true);

            $order = Order::create([
                'order_number' => $this->nextOrderNumber(),
                'user_id' => $request->user()->id,
                'voucher_id' => $quote['voucher']['id'] ?? null,
                'shipping_area_id' => $quote['shipping_area']['id'],
                'customer_name' => $request->user()->name,
                'customer_phone' => $data['customer_phone'],
                'shipping_address' => $location['formatted_address'],
                'shipping_city' => $location['city'],
                'shipping_district' => $location['district'],
                'shipping_postal_code' => $location['postal_code'],
                'shipping_latitude' => $location['latitude'],
                'shipping_longitude' => $location['longitude'],
                'shipping_note' => $data['shipping_note'] ?? null,
                'subtotal_amount' => $quote['subtotal'],
                'discount_amount' => $quote['discount_amount'],
                'shipping_cost' => $quote['shipping_cost'],
                'total_amount' => $quote['total'],
                'order_status' => 'menunggu_pembayaran',
                'payment_status' => 'pending',
            ]);

            foreach ($cartItems as $item) {
                $variant = $item->variant;

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product->name,
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

            if ($quote['voucher']) {
                Voucher::whereKey($quote['voucher']['id'])->increment('used_count');
                VoucherUsage::create([
                    'voucher_id' => $quote['voucher']['id'],
                    'user_id' => $request->user()->id,
                    'order_id' => $order->id,
                    'discount_amount' => $quote['discount_amount'],
                    'used_at' => now(),
                ]);
            }

            CartItem::where('user_id', $request->user()->id)->delete();
            $request->user()->forceFill(['phone' => $data['customer_phone']])->save();

            return $order;
        });

        $request->session()->forget(['checkout.location', 'checkout.voucher_code']);
        $payments->createAttempt($order);

        return redirect()
            ->route('checkout.index', ['order' => $order->id])
            ->with('success', 'Pesanan dibuat dan menunggu pembayaran Midtrans.');
    }

    private function cartItems(Request $request): Collection
    {
        return CartItem::query()
            ->with([
                'product.category:id,name',
                'product.primaryImage:id,product_id,file_path,alt_text',
                'variant:id,product_id,sku,variant_name,size,material,color,price,stock,reserved_stock,status',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();
    }

    private function cartPayload(CartItem $item): array
    {
        return [
            'id' => $item->id,
            'product_name' => $item->product->name,
            'product_slug' => $item->product->slug,
            'category' => $item->product->category?->name,
            'image_url' => $item->product->primaryImage?->file_path ? asset('storage/'.$item->product->primaryImage->file_path) : null,
            'variant_name' => $item->variant->variant_name ?: $item->variant->sku,
            'specification' => collect([$item->variant->size, $item->variant->material, $item->variant->color])->filter()->join(' / '),
            'unit_price' => (float) $item->variant->price,
            'quantity' => $item->quantity,
            'subtotal' => (float) $item->variant->price * $item->quantity,
        ];
    }

    private function quote(Collection $items, int $userId, ?array $location, string $voucherCode, bool $strict, bool $lockVoucher = false): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $this->assertCartItemValid($item);
            $subtotal += (float) $item->variant->price * $item->quantity;
        }

        $shippingArea = $location ? $this->matchingShippingArea((float) $location['latitude'], (float) $location['longitude']) : null;

        if ($strict && ! $shippingArea) {
            throw ValidationException::withMessages([
                'location' => 'Alamat belum masuk wilayah layanan toko.',
            ]);
        }

        $voucher = $voucherCode !== ''
            ? $this->validVoucher($voucherCode, $subtotal, $userId, $lockVoucher)
            : null;
        $discount = $voucher ? $this->discountAmount($voucher, $subtotal) : 0.0;
        $shippingCost = $shippingArea ? (float) $shippingArea->shipping_cost : 0.0;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => min($discount, $subtotal),
            'shipping_cost' => $shippingCost,
            'total' => max(0, $subtotal - min($discount, $subtotal)) + $shippingCost,
            'shipping_area' => $shippingArea ? [
                'id' => $shippingArea->id,
                'name' => $shippingArea->name,
                'shipping_cost' => (float) $shippingArea->shipping_cost,
                'priority' => $shippingArea->priority,
            ] : null,
            'voucher' => $voucher ? [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->name,
                'discount_type' => $voucher->discount_type,
                'discount_value' => (float) $voucher->discount_value,
            ] : null,
            'can_submit' => $items->isNotEmpty() && $location !== null && $shippingArea !== null,
        ];
    }

    private function assertCartItemValid(CartItem $item): void
    {
        if ($item->product->status !== 'aktif') {
            throw ValidationException::withMessages(['cart' => "{$item->product->name} sudah tidak aktif."]);
        }

        if ($item->variant->status !== 'aktif') {
            throw ValidationException::withMessages(['cart' => "Varian {$item->product->name} sudah tidak aktif."]);
        }

        if ($item->quantity > $item->variant->availableStock()) {
            throw ValidationException::withMessages(['cart' => "Stok {$item->product->name} tidak mencukupi."]);
        }
    }

    private function matchingShippingArea(float $latitude, float $longitude): ?ShippingArea
    {
        $maps = app(GoogleMapsClient::class);

        return ShippingArea::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (ShippingArea $area) use ($maps, $latitude, $longitude) {
                $distance = $maps->distanceInMeters(
                    (float) $area->center_latitude,
                    (float) $area->center_longitude,
                    $latitude,
                    $longitude
                );

                return $distance <= ((float) $area->radius_km * 1000);
            })
            ->sortByDesc('priority')
            ->first();
    }

    private function validVoucher(string $code, float $subtotal, int $userId, bool $lock): Voucher
    {
        $query = Voucher::query()->where('code', $code);

        if ($lock) {
            $query->lockForUpdate();
        }

        $voucher = $query->first();

        if (! $voucher || $voucher->status !== 'aktif') {
            throw ValidationException::withMessages(['voucher_code' => 'Voucher tidak valid.']);
        }

        if ($voucher->start_at->isFuture() || $voucher->end_at->isPast()) {
            throw ValidationException::withMessages(['voucher_code' => 'Voucher sudah berakhir atau belum aktif.']);
        }

        if ($voucher->quota !== null && $voucher->used_count >= $voucher->quota) {
            throw ValidationException::withMessages(['voucher_code' => 'Kuota voucher sudah habis.']);
        }

        if ($subtotal < (float) $voucher->minimum_purchase) {
            throw ValidationException::withMessages(['voucher_code' => 'Subtotal belum memenuhi minimum pembelian voucher.']);
        }

        if ($voucher->per_user_limit !== null) {
            $usedByUser = $voucher->usages()->where('user_id', $userId)->count();

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

    private function locationFromGeocode(array $geocode): ?array
    {
        $result = $geocode['results'][0] ?? null;
        $lat = $result['geometry']['location']['lat'] ?? null;
        $lng = $result['geometry']['location']['lng'] ?? null;
        $address = $result['formatted_address'] ?? null;

        if (! $result || $address === null || $lat === null || $lng === null) {
            return null;
        }

        $components = collect($result['address_components'] ?? []);

        return [
            'formatted_address' => $address,
            'city' => $this->addressComponent($components, ['administrative_area_level_2', 'locality']),
            'district' => $this->addressComponent($components, ['administrative_area_level_3', 'sublocality']),
            'postal_code' => $this->addressComponent($components, ['postal_code']),
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
            'place_id' => $result['place_id'] ?? null,
        ];
    }

    private function addressComponent($components, array $types): ?string
    {
        $component = $components->first(fn ($component) => count(array_intersect($component['types'] ?? [], $types)) > 0);

        return $component['long_name'] ?? null;
    }

    private function createdOrder(Request $request): ?array
    {
        $orderId = $request->integer('order');

        if (! $orderId) {
            return null;
        }

        $order = Order::query()
            ->with(['items', 'payments' => fn ($query) => $query->latest('attempt_number')])
            ->where('user_id', $request->user()->id)
            ->findOrFail($orderId);

        $latestPayment = $order->payments->first();

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'total_amount' => (float) $order->total_amount,
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
            'payment' => $latestPayment ? [
                'id' => $latestPayment->id,
                'attempt_number' => $latestPayment->attempt_number,
                'midtrans_order_id' => $latestPayment->midtrans_order_id,
                'status' => $latestPayment->status,
                'transaction_status' => $latestPayment->transaction_status,
                'gross_amount' => (float) $latestPayment->gross_amount,
                'snap_token' => $latestPayment->snap_token,
                'redirect_url' => $latestPayment->redirect_url,
                'expired_at' => $latestPayment->expired_at?->toIso8601String(),
            ] : null,
            'can_create_payment_attempt' => ! $order->payments->contains('status', 'pending')
                && ! $order->payments->contains('status', 'success')
                && $order->order_status !== 'dibatalkan',
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ]),
        ];
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
            'shipping_area' => null,
            'voucher' => null,
            'can_submit' => false,
        ];
    }
}
