<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { CreditCard, MapPin, PackageCheck, X } from '@lucide/vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import MapPickerShell from '@/Components/UI/MapPickerShell.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import VoucherInput from '@/Components/UI/VoucherInput.vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
    location: { type: Object, default: null },
    voucherCode: { type: String, default: '' },
    googleMaps: { type: Object, default: () => ({}) },
    midtrans: { type: Object, default: () => ({}) },
    createdOrder: { type: Object, default: null },
});

const page = usePage();
const sofaFallback = 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80';
const paymentModalOpen = ref(false);
const snapLoading = ref(false);

const locationForm = useForm({
    place_id: props.location?.place_id || 'demo-checkout-location',
});

const quoteForm = useForm({
    voucher_code: props.voucherCode || '',
});

const orderForm = useForm({
    customer_phone: page.props.auth.user?.phone || '',
    shipping_note: '',
    voucher_code: props.voucherCode || '',
});
const retryPaymentForm = useForm({});

const canCreateOrder = computed(() => props.summary.can_submit && props.items.length > 0 && !props.createdOrder);
const canOpenPendingPayment = computed(() => {
    if (props.createdOrder?.payment?.status !== 'pending' || !props.createdOrder.payment.snap_token) {
        return false;
    }

    return !props.createdOrder.payment.expired_at || new Date(props.createdOrder.payment.expired_at) > new Date();
});

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}

function resolveLocation() {
    locationForm.post(route('checkout.location'), {
        preserveScroll: true,
    });
}

function applyVoucher() {
    quoteForm.post(route('checkout.quote'), {
        preserveScroll: true,
        onSuccess: () => {
            orderForm.voucher_code = quoteForm.voucher_code;
        },
    });
}

function submitOrder() {
    orderForm.voucher_code = quoteForm.voucher_code;
    orderForm.post(route('checkout.store'), {
        preserveScroll: true,
    });
}

function snapScriptUrl() {
    return props.midtrans?.isProduction
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
}

function ensureSnapScript() {
    if (window.snap || !props.midtrans?.clientKey) {
        return Promise.resolve();
    }

    const existingScript = document.querySelector('script[data-midtrans-snap]');

    if (existingScript) {
        if (existingScript.dataset.loaded === 'true') {
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            existingScript.addEventListener('load', resolve, { once: true });
            existingScript.addEventListener('error', reject, { once: true });
        });
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = snapScriptUrl();
        script.async = true;
        script.setAttribute('data-midtrans-snap', 'true');
        script.setAttribute('data-client-key', props.midtrans.clientKey);
        script.addEventListener('load', () => {
            script.dataset.loaded = 'true';
            resolve();
        }, { once: true });
        script.addEventListener('error', reject, { once: true });
        document.head.appendChild(script);
    });
}

async function openPaymentModal() {
    const token = props.createdOrder?.payment?.snap_token;

    if (!token) {
        paymentModalOpen.value = true;
        return;
    }

    snapLoading.value = true;

    try {
        await ensureSnapScript();

        if (window.snap) {
            window.snap.pay(token);
            return;
        }
    } catch (error) {
        // Fallback modal keeps the payment token reachable when Snap script is not available.
    } finally {
        snapLoading.value = false;
    }

    paymentModalOpen.value = true;
}

function createPaymentAttempt() {
    retryPaymentForm.post(route('payments.store', props.createdOrder.id), {
        preserveScroll: true,
    });
}

onMounted(() => {
    if (canOpenPendingPayment.value) {
        openPaymentModal();
    }
});
</script>

<template>
    <Head title="Checkout" />

    <PublicLayout>
        <section class="bg-neutral-light py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-neutral-text">Checkout</h1>
                        <p class="mt-1 text-sm text-neutral-muted">Periksa pesanan, alamat, voucher, dan total pembayaran.</p>
                    </div>
                    <AppButton href="/cart" variant="secondary">Kembali ke Keranjang</AppButton>
                </div>

                <div v-if="page.props.flash?.success" class="mb-4">
                    <Alert tone="success">{{ page.props.flash.success }}</Alert>
                </div>
                <div v-if="page.props.flash?.error" class="mb-4">
                    <Alert tone="danger">{{ page.props.flash.error }}</Alert>
                </div>

                <section v-if="createdOrder" class="mb-5 rounded-md border border-green-200 bg-green-50 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <PackageCheck class="h-5 w-5 text-success" />
                                <h2 class="text-lg font-semibold text-neutral-text">Pesanan dibuat</h2>
                            </div>
                            <p class="mt-2 text-sm text-neutral-muted">
                                Nomor pesanan {{ createdOrder.order_number }}
                                {{ createdOrder.payment_status === 'success' ? 'sudah dibayar.' : 'menunggu pembayaran Midtrans.' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge :status="createdOrder.order_status" />
                            <StatusBadge :status="createdOrder.payment_status" />
                        </div>
                    </div>
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xl font-semibold text-neutral-text">{{ formatRupiah(createdOrder.total_amount) }}</p>
                        <AppButton v-if="canOpenPendingPayment" :loading="snapLoading" @click="openPaymentModal">
                            <CreditCard class="h-4 w-4" />
                            Buka Modal Midtrans
                        </AppButton>
                        <AppButton v-else-if="createdOrder.can_create_payment_attempt" :loading="retryPaymentForm.processing" @click="createPaymentAttempt">
                            <CreditCard class="h-4 w-4" />
                            Coba Bayar Lagi
                        </AppButton>
                    </div>
                    <Alert v-if="createdOrder.payment?.status === 'pending'" tone="info" class="mt-4">
                        Pembayaran diproses melalui Midtrans Snap. Jika modal ditutup, status tetap pending sampai callback diterima.
                    </Alert>
                    <Alert v-else-if="createdOrder.payment?.status === 'expired'" tone="warning" class="mt-4">
                        Batas waktu pembayaran sudah habis. Buat payment attempt baru untuk mencoba lagi.
                    </Alert>
                    <Alert v-else-if="['failed', 'cancelled'].includes(createdOrder.payment?.status)" tone="warning" class="mt-4">
                        Pembayaran belum berhasil. Kamu dapat mencoba payment attempt baru selama pesanan belum dibatalkan atau dibayar.
                    </Alert>
                    <Alert v-if="createdOrder.order_status === 'perlu_review_admin'" tone="warning" class="mt-4">
                        Pembayaran diterima, tetapi stok perlu dicek admin sebelum pesanan diproses.
                    </Alert>
                    <Alert v-if="retryPaymentForm.errors.order || retryPaymentForm.errors.stock" tone="danger" class="mt-4">
                        {{ retryPaymentForm.errors.order || retryPaymentForm.errors.stock }}
                    </Alert>
                    <div v-if="createdOrder.items?.length" class="mt-4 grid gap-2 border-t border-green-200 pt-4 text-sm">
                        <div v-for="item in createdOrder.items" :key="`${item.product_name}-${item.variant_name}`" class="flex justify-between gap-3">
                            <span class="text-neutral-muted">{{ item.product_name }} - {{ item.variant_name || 'Varian standar' }} x {{ item.quantity }}</span>
                            <span class="font-semibold text-neutral-text">{{ formatRupiah(item.subtotal) }}</span>
                        </div>
                    </div>
                </section>

                <EmptyState v-if="items.length === 0 && !createdOrder" title="Keranjang kosong" message="Pilih produk sebelum membuat pesanan.">
                    <template #actions>
                        <AppButton href="/catalog">Lihat Katalog</AppButton>
                    </template>
                </EmptyState>

                <div v-if="items.length > 0" class="grid gap-5 lg:grid-cols-[1fr_380px]">
                    <div class="grid gap-5">
                        <section class="rounded-md border border-neutral-border bg-white p-5">
                            <h2 class="text-lg font-semibold text-neutral-text">Item pesanan</h2>
                            <div class="mt-4 grid gap-3">
                                <article v-for="item in items" :key="item.id" class="grid gap-3 rounded-md border border-neutral-border p-3 sm:grid-cols-[88px_1fr_auto]">
                                    <img :src="item.image_url || sofaFallback" :alt="item.product_name" class="aspect-square w-full rounded-md object-cover" />
                                    <div>
                                        <Link :href="route('products.show', item.product_slug)" class="font-semibold text-neutral-text hover:text-primary-hover">{{ item.product_name }}</Link>
                                        <p class="mt-1 text-sm text-neutral-muted">{{ item.variant_name || 'Varian standar' }}</p>
                                        <p class="mt-1 text-sm text-neutral-muted">{{ item.specification || item.category || '-' }}</p>
                                        <p class="mt-2 text-sm text-neutral-muted">{{ item.quantity }} x {{ formatRupiah(item.unit_price) }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-neutral-text">{{ formatRupiah(item.subtotal) }}</p>
                                </article>
                            </div>
                        </section>

                        <section v-if="!createdOrder" class="rounded-md border border-neutral-border bg-white p-5">
                            <h2 class="text-lg font-semibold text-neutral-text">Kontak dan alamat</h2>
                            <div class="mt-4 grid gap-4">
                                <FormInput id="customer_phone" v-model="orderForm.customer_phone" label="Nomor telepon" :error="orderForm.errors.customer_phone" required />

                                <MapPickerShell title="Alamat Pengiriman" :address="location?.formatted_address || ''" :error="locationForm.errors.place_id || quoteForm.errors.location || orderForm.errors.location">
                                    <template #actions>
                                        <StatusBadge :status="location ? 'aktif' : 'pending'" :label="location ? 'Terpilih' : 'Belum dipilih'" />
                                    </template>
                                    <div class="w-full max-w-xl text-left">
                                        <div class="rounded-md border border-neutral-border bg-white p-4">
                                            <label class="block" for="place_id">
                                                <span class="text-sm font-medium text-neutral-text">Pencarian lokasi</span>
                                                <input
                                                    id="place_id"
                                                    v-model="locationForm.place_id"
                                                    class="mt-1 block min-h-10 w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary"
                                                    type="text"
                                                    placeholder="Masukkan hasil pencarian lokasi"
                                                />
                                            </label>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                <AppButton type="button" :loading="locationForm.processing" @click="resolveLocation">
                                                    <MapPin class="h-4 w-4" />
                                                    Pilih Lokasi
                                                </AppButton>
                                            </div>
                                            <div v-if="location" class="mt-4 rounded-md bg-neutral-light p-3 text-sm text-neutral-muted">
                                                <p class="font-semibold text-neutral-text">{{ location.formatted_address }}</p>
                                                <p v-if="location.city || location.district || location.postal_code" class="mt-1">
                                                    {{ [location.city, location.district, location.postal_code].filter(Boolean).join(', ') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </MapPickerShell>

                                <label class="block" for="shipping_note">
                                    <span class="text-sm font-medium text-neutral-text">Catatan alamat</span>
                                    <textarea id="shipping_note" v-model="orderForm.shipping_note" rows="3" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" placeholder="Nomor rumah, blok, patokan, atau instruksi pengiriman" />
                                    <p v-if="orderForm.errors.shipping_note" class="mt-1 text-sm text-danger">{{ orderForm.errors.shipping_note }}</p>
                                </label>
                            </div>
                        </section>
                    </div>

                    <aside class="h-fit rounded-md border border-neutral-border bg-white p-5">
                        <h2 class="text-lg font-semibold text-neutral-text">Ringkasan pembayaran</h2>

                        <div v-if="!createdOrder" class="mt-4">
                            <VoucherInput v-model="quoteForm.voucher_code" :loading="quoteForm.processing" :error="quoteForm.errors.voucher_code" @apply="applyVoucher" />
                        </div>

                        <div class="mt-5 grid gap-3 text-sm">
                            <div class="flex justify-between gap-3">
                                <span class="text-neutral-muted">Subtotal produk</span>
                                <span class="font-semibold text-neutral-text">{{ formatRupiah(summary.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-neutral-muted">Diskon voucher</span>
                                <span class="font-semibold text-neutral-text">-{{ formatRupiah(summary.discount_amount) }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-neutral-muted">Ongkir internal</span>
                                <span class="font-semibold text-neutral-text">{{ summary.shipping_area ? formatRupiah(summary.shipping_cost) : 'Pilih alamat' }}</span>
                            </div>
                            <div v-if="summary.voucher" class="flex justify-between gap-3">
                                <span class="text-neutral-muted">Voucher</span>
                                <StatusBadge status="aktif" :label="summary.voucher.code" />
                            </div>
                            <div v-if="summary.shipping_area" class="flex justify-between gap-3">
                                <span class="text-neutral-muted">Area ongkir</span>
                                <span class="text-right font-semibold text-neutral-text">{{ summary.shipping_area.name }}</span>
                            </div>
                            <div class="flex justify-between gap-3 border-t border-neutral-border pt-3 text-base">
                                <span class="font-semibold text-neutral-text">Total</span>
                                <span class="font-bold text-neutral-text">{{ formatRupiah(summary.total) }}</span>
                            </div>
                        </div>

                        <Alert v-if="!summary.can_submit && !createdOrder" tone="warning" class="mt-4">
                            Pilih alamat yang masuk area layanan sebelum membuat pesanan.
                        </Alert>
                        <Alert v-if="orderForm.errors.cart" tone="danger" class="mt-4">{{ orderForm.errors.cart }}</Alert>

                        <div v-if="!createdOrder" class="mt-5">
                            <AppButton class="w-full" :disabled="!canCreateOrder" :loading="orderForm.processing" @click="submitOrder">
                                Buat Pesanan
                            </AppButton>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <div v-if="paymentModalOpen" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
            <section class="w-full max-w-lg rounded-md bg-white p-5 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="payment-modal-title" class="text-lg font-semibold text-neutral-text">Midtrans Snap</h2>
                        <p class="mt-1 text-sm text-neutral-muted">Token pembayaran sudah dibuat untuk pesanan {{ createdOrder.order_number }}.</p>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="paymentModalOpen = false">
                        <X class="h-4 w-4" />
                        <span class="sr-only">Tutup</span>
                    </button>
                </div>
                <div class="mt-4 rounded-md border border-neutral-border bg-neutral-light p-4 text-sm">
                    <p class="font-semibold text-neutral-text">Total: {{ formatRupiah(createdOrder.total_amount) }}</p>
                    <p class="mt-2 break-all text-neutral-muted">Snap token: {{ createdOrder.payment?.snap_token || 'Belum tersedia' }}</p>
                </div>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <a
                        v-if="createdOrder.payment?.redirect_url"
                        :href="createdOrder.payment.redirect_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-neutral-border bg-white px-4 text-sm font-semibold text-neutral-text transition hover:bg-neutral-light focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        Buka Snap
                    </a>
                    <AppButton type="button" @click="paymentModalOpen = false">Tutup</AppButton>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
