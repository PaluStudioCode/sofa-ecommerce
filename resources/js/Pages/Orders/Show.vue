<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, MapPin, PackageCheck, Truck, X } from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';

const props = defineProps({
    order: { type: Object, required: true },
    paymentGateway: { type: Object, default: () => ({}) },
});

const paymentModalOpen = ref(false);
const activeModal = ref(null);
const snapLoading = ref(false);
const retryPaymentForm = useForm({ return_to: 'order' });

const latestPayment = computed(() => props.order.payment);
const canOpenPayment = computed(() => props.order.can_open_payment && latestPayment.value?.snap_token);
const addressMeta = computed(() => [props.order.shipping_city, props.order.shipping_district, props.order.shipping_postal_code].filter(Boolean).join(', '));

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}

function formatDate(value) {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function snapScriptUrl() {
    return props.paymentGateway?.isProduction
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
}

function ensureSnapScript() {
    if (window.snap || !props.paymentGateway?.clientKey) {
        return Promise.resolve();
    }

    const existingScript = document.querySelector('script[data-payment-script]');

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
        script.setAttribute('data-payment-script', 'true');
        script.setAttribute('data-client-key', props.paymentGateway.clientKey);
        script.addEventListener('load', () => {
            script.dataset.loaded = 'true';
            resolve();
        }, { once: true });
        script.addEventListener('error', reject, { once: true });
        document.head.appendChild(script);
    });
}

async function openPaymentModal() {
    const token = latestPayment.value?.snap_token;

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
        // Fallback modal keeps the active payment link reachable.
    } finally {
        snapLoading.value = false;
    }

    paymentModalOpen.value = true;
}

function createPaymentAttempt() {
    retryPaymentForm.post(route('payments.store', props.order.id), {
        preserveScroll: true,
    });
}

function openInfoModal(name) {
    activeModal.value = name;
}

function closeInfoModal() {
    activeModal.value = null;
}

onMounted(() => {
    const params = new URL(window.location.href).searchParams;

    if ((params.has('new_order') || params.has('payment_attempt')) && canOpenPayment.value) {
        openPaymentModal();
    }
});
</script>

<template>
    <Head :title="`Detail Pesanan ${order.order_number}`" />

    <PublicLayout>
        <section class="bg-neutral-light py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <Link :href="route('orders.index')" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-muted hover:text-neutral-text">
                            <ArrowLeft class="h-4 w-4" />
                            Riwayat Pesanan
                        </Link>
                        <h1 class="mt-3 text-3xl font-bold text-neutral-text">{{ order.order_number }}</h1>
                        <p class="mt-1 text-sm text-neutral-muted">Dibuat {{ formatDate(order.created_at) }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <StatusBadge :status="order.order_status" />
                        <StatusBadge :status="order.payment_status" />
                        <StatusBadge :status="order.shipment_status" :label="order.shipment_label" />
                    </div>
                </div>

                <Alert v-if="order.order_status === 'perlu_review_admin'" tone="warning" class="mb-5">
                    Pembayaran diterima, tetapi pesanan sedang dicek admin karena stok perlu diverifikasi.
                </Alert>
                <Alert v-if="['failed', 'expired', 'cancelled'].includes(latestPayment?.status)" tone="warning" class="mb-5">
                    Pembayaran terakhir belum berhasil. Buat pembayaran baru jika pesanan belum dibatalkan dan belum dibayar.
                </Alert>
                <Alert v-if="retryPaymentForm.errors.order || retryPaymentForm.errors.stock" tone="danger" class="mb-5">
                    {{ retryPaymentForm.errors.order || retryPaymentForm.errors.stock }}
                </Alert>

                <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="grid min-w-0 gap-5">
                        <section class="rounded-md border border-neutral-border bg-white p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-neutral-text">Status Pesanan</h2>
                                    <p class="mt-1 text-sm text-neutral-muted">Perkembangan pesanan dari pembayaran sampai pengiriman selesai.</p>
                                </div>
                                <StatusBadge :status="order.shipment_status" :label="order.shipment_label" />
                            </div>

                            <ol class="mt-5 grid gap-3">
                                <li v-for="step in order.timeline" :key="step.label" class="grid grid-cols-[28px_1fr] gap-3">
                                    <span
                                        class="mt-1 grid h-7 w-7 place-items-center rounded-full border text-xs font-bold"
                                        :class="{
                                            'border-green-200 bg-green-50 text-success': step.state === 'completed',
                                            'border-yellow-200 bg-primary-soft text-neutral-text': step.state === 'current',
                                            'border-red-200 bg-red-50 text-danger': step.state === 'blocked',
                                            'border-neutral-border bg-neutral-light text-neutral-muted': step.state === 'pending',
                                        }"
                                    >
                                        <PackageCheck v-if="step.state === 'completed'" class="h-4 w-4" />
                                        <span v-else class="h-2 w-2 rounded-full bg-current" />
                                    </span>
                                    <div>
                                        <p class="font-semibold text-neutral-text">{{ step.label }}</p>
                                        <p class="mt-1 text-sm text-neutral-muted">{{ step.description }}</p>
                                    </div>
                                </li>
                            </ol>
                        </section>

                        <section class="rounded-md border border-neutral-border bg-white p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <h2 class="text-lg font-semibold text-neutral-text">Item Pesanan</h2>
                                <p class="text-sm text-neutral-muted">{{ order.items.length }} item</p>
                            </div>
                            <div class="mt-4 grid gap-3">
                                <article v-for="item in order.items" :key="`${item.product_name}-${item.variant_sku}`" class="rounded-md border border-neutral-border p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="font-semibold text-neutral-text">{{ item.product_name }}</p>
                                            <p class="mt-1 text-sm text-neutral-muted">{{ item.variant_name || 'Varian standar' }}</p>
                                            <p class="mt-1 text-sm text-neutral-muted">
                                                {{ [item.variant_size, item.variant_material, item.variant_color].filter(Boolean).join(' / ') || '-' }}
                                            </p>
                                        </div>
                                        <div class="text-left sm:text-right">
                                            <p class="text-sm text-neutral-muted">{{ item.quantity }} x {{ formatRupiah(item.product_price) }}</p>
                                            <p class="mt-1 font-semibold text-neutral-text">{{ formatRupiah(item.subtotal) }}</p>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>

                    <aside class="grid h-fit gap-5">
                        <section class="rounded-md border border-neutral-border bg-white p-5">
                            <div class="flex items-center gap-2">
                                <CreditCard class="h-5 w-5 text-primary-hover" />
                                <h2 class="text-lg font-semibold text-neutral-text">Pembayaran</h2>
                            </div>

                            <div class="mt-5 rounded-md bg-neutral-light p-4">
                                <p class="text-sm text-neutral-muted">Total tagihan</p>
                                <p class="mt-1 text-2xl font-bold text-neutral-text">{{ formatRupiah(order.total_amount) }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <StatusBadge :status="order.payment_status" />
                                    <StatusBadge v-if="latestPayment" :status="latestPayment.status" :label="`Pembayaran #${latestPayment.attempt_number}`" />
                                </div>
                            </div>

                            <div v-if="latestPayment" class="mt-4 grid gap-3 text-sm">
                                <div v-if="latestPayment.payment_type" class="flex justify-between gap-3">
                                    <span class="text-neutral-muted">Metode</span>
                                    <span class="font-semibold text-neutral-text">{{ latestPayment.payment_type }}</span>
                                </div>
                                <div v-if="latestPayment.paid_at" class="flex justify-between gap-3">
                                    <span class="text-neutral-muted">Dibayar</span>
                                    <span class="text-right font-semibold text-neutral-text">{{ formatDate(latestPayment.paid_at) }}</span>
                                </div>
                                <div v-if="latestPayment.expired_at && latestPayment.status === 'pending'" class="flex justify-between gap-3">
                                    <span class="text-neutral-muted">Batas bayar</span>
                                    <span class="text-right font-semibold text-neutral-text">{{ formatDate(latestPayment.expired_at) }}</span>
                                </div>
                            </div>
                            <p v-else class="mt-4 text-sm text-neutral-muted">Pembayaran belum tersedia.</p>

                            <div class="mt-5 grid gap-2">
                                <AppButton v-if="canOpenPayment" class="w-full" :loading="snapLoading" @click="openPaymentModal">
                                    <CreditCard class="h-4 w-4" />
                                    Bayar Sekarang
                                </AppButton>
                                <AppButton v-else-if="order.can_create_payment_attempt" class="w-full" :loading="retryPaymentForm.processing" @click="createPaymentAttempt">
                                    <CreditCard class="h-4 w-4" />
                                    Coba Bayar Lagi
                                </AppButton>
                                <AppButton class="w-full" variant="secondary" @click="openInfoModal('billing')">Rincian Biaya</AppButton>
                            </div>
                        </section>

                        <section class="rounded-md border border-neutral-border bg-white p-5">
                            <h2 class="text-lg font-semibold text-neutral-text">Informasi Pesanan</h2>
                            <div class="mt-4 grid gap-2">
                                <AppButton class="w-full" variant="secondary" @click="openInfoModal('address')">
                                    <MapPin class="h-4 w-4" />
                                    Alamat Pengiriman
                                </AppButton>
                                <AppButton class="w-full" variant="secondary" @click="openInfoModal('shipment')">
                                    <Truck class="h-4 w-4" />
                                    Pengiriman
                                </AppButton>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </section>

        <Modal :show="activeModal === 'billing'" max-width="lg" @close="closeInfoModal">
            <section class="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-text">Rincian Biaya</h2>
                        <p class="mt-1 text-sm text-neutral-muted">{{ order.order_number }}</p>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="closeInfoModal">
                        <X class="h-4 w-4" />
                        <span class="sr-only">Tutup</span>
                    </button>
                </div>
                <div class="mt-5 grid gap-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-neutral-muted">Subtotal produk</span>
                        <span class="font-semibold text-neutral-text">{{ formatRupiah(order.subtotal_amount) }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-neutral-muted">Diskon voucher</span>
                        <span class="font-semibold text-neutral-text">-{{ formatRupiah(order.discount_amount) }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-neutral-muted">Ongkir internal</span>
                        <span class="font-semibold text-neutral-text">{{ formatRupiah(order.shipping_cost) }}</span>
                    </div>
                    <div v-if="order.voucher" class="flex justify-between gap-3">
                        <span class="text-neutral-muted">Voucher</span>
                        <StatusBadge :status="order.voucher.status" :label="order.voucher.code" />
                    </div>
                    <div class="flex justify-between gap-3 border-t border-neutral-border pt-3 text-base">
                        <span class="font-semibold text-neutral-text">Total</span>
                        <span class="font-bold text-neutral-text">{{ formatRupiah(order.total_amount) }}</span>
                    </div>
                </div>
            </section>
        </Modal>

        <Modal :show="activeModal === 'address'" max-width="lg" @close="closeInfoModal">
            <section class="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-text">Alamat Pengiriman</h2>
                        <p class="mt-1 text-sm text-neutral-muted">Kontak dan lokasi penerima.</p>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="closeInfoModal">
                        <X class="h-4 w-4" />
                        <span class="sr-only">Tutup</span>
                    </button>
                </div>
                <div class="mt-5 grid gap-3 text-sm">
                    <p class="font-semibold text-neutral-text">{{ order.customer_name }} - {{ order.customer_phone }}</p>
                    <p class="text-neutral-muted">{{ order.shipping_address }}</p>
                    <p v-if="addressMeta" class="text-neutral-muted">{{ addressMeta }}</p>
                    <p v-if="order.shipping_note" class="rounded-md bg-neutral-light p-3 text-neutral-muted">{{ order.shipping_note }}</p>
                    <p v-if="order.store" class="text-neutral-muted">Titik asal pengiriman: <span class="font-semibold text-neutral-text">{{ order.store.name }}</span></p>
                </div>
            </section>
        </Modal>

        <Modal :show="activeModal === 'shipment'" max-width="lg" @close="closeInfoModal">
            <section class="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-text">Pengiriman</h2>
                        <p class="mt-1 text-sm text-neutral-muted">{{ order.shipment_summary }}</p>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="closeInfoModal">
                        <X class="h-4 w-4" />
                        <span class="sr-only">Tutup</span>
                    </button>
                </div>
                <div class="mt-5 grid gap-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-neutral-muted">Status</span>
                        <StatusBadge :status="order.shipment_status" :label="order.shipment_label" />
                    </div>
                    <div v-if="order.shipment?.scheduled_at" class="flex justify-between gap-3">
                        <span class="text-neutral-muted">Jadwal</span>
                        <span class="text-right font-semibold text-neutral-text">{{ formatDate(order.shipment.scheduled_at) }}</span>
                    </div>
                    <div v-if="order.shipment?.delivered_at" class="flex justify-between gap-3">
                        <span class="text-neutral-muted">Terkirim</span>
                        <span class="text-right font-semibold text-neutral-text">{{ formatDate(order.shipment.delivered_at) }}</span>
                    </div>
                    <div v-if="order.shipment?.driver_name" class="flex justify-between gap-3">
                        <span class="text-neutral-muted">Petugas</span>
                        <span class="text-right font-semibold text-neutral-text">{{ order.shipment.driver_name }}</span>
                    </div>
                    <div v-if="order.shipment?.driver_phone" class="flex justify-between gap-3">
                        <span class="text-neutral-muted">Nomor petugas</span>
                        <span class="text-right font-semibold text-neutral-text">{{ order.shipment.driver_phone }}</span>
                    </div>
                    <div v-if="order.shipment?.vehicle_note" class="flex justify-between gap-3">
                        <span class="text-neutral-muted">Kendaraan</span>
                        <span class="text-right font-semibold text-neutral-text">{{ order.shipment.vehicle_note }}</span>
                    </div>
                    <p v-if="order.shipment?.shipping_note" class="rounded-md bg-neutral-light p-3 text-neutral-muted">{{ order.shipment.shipping_note }}</p>
                </div>
            </section>
        </Modal>

        <Modal :show="paymentModalOpen" max-width="lg" @close="paymentModalOpen = false">
            <section class="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-text">Lanjutkan Pembayaran</h2>
                        <p class="mt-1 text-sm text-neutral-muted">Pembayaran aktif untuk pesanan {{ order.order_number }}.</p>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="paymentModalOpen = false">
                        <X class="h-4 w-4" />
                        <span class="sr-only">Tutup</span>
                    </button>
                </div>
                <div class="mt-4 rounded-md border border-neutral-border bg-neutral-light p-4 text-sm">
                    <p class="font-semibold text-neutral-text">Total: {{ formatRupiah(order.total_amount) }}</p>
                    <p class="mt-2 text-neutral-muted">Jika jendela pembayaran tidak terbuka otomatis, gunakan tombol di bawah ini.</p>
                </div>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <a
                        v-if="latestPayment?.redirect_url"
                        :href="latestPayment.redirect_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-primary bg-primary px-4 text-sm font-semibold text-neutral-text transition hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        Bayar Sekarang
                    </a>
                    <AppButton type="button" variant="secondary" @click="paymentModalOpen = false">Tutup</AppButton>
                </div>
            </section>
        </Modal>
    </PublicLayout>
</template>
