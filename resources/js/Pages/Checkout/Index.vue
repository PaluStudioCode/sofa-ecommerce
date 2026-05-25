<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import VoucherInput from '@/Components/UI/VoucherInput.vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
    location: { type: Object, default: null },
    voucherCode: { type: String, default: '' },
});

const sofaFallback = 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80';

const quoteForm = useForm({
    voucher_code: props.voucherCode || '',
});

const orderForm = useForm({
    voucher_code: props.voucherCode || '',
});

const hasSavedAddress = computed(() => Boolean(props.location?.formatted_address)
    && props.location?.latitude !== null
    && props.location?.latitude !== undefined
    && props.location?.longitude !== null
    && props.location?.longitude !== undefined);
const hasRecipientDetails = computed(() => Boolean(props.location?.recipient_name) && Boolean(props.location?.phone) && Boolean(props.location?.detail));
const isAddressReady = computed(() => hasSavedAddress.value && hasRecipientDetails.value);
const canCreateOrder = computed(() => props.summary.can_submit && props.items.length > 0 && isAddressReady.value);
const addressMeta = computed(() => [props.location?.district, props.location?.city, props.location?.postal_code].filter(Boolean).join(', '));

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}

function formatKilometer(value) {
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 2,
    }).format(value || 0);
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

                <EmptyState v-if="items.length === 0" title="Keranjang kosong" message="Pilih produk sebelum membuat pesanan.">
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

                        <section class="rounded-md border border-neutral-border bg-white p-5">
                            <h2 class="text-lg font-semibold text-neutral-text">Kontak dan alamat</h2>
                            <div class="mt-4 grid gap-4">
                                <section class="rounded-md border border-neutral-border bg-neutral-light p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex items-start gap-3">
                                            <MapPin class="mt-0.5 h-5 w-5 shrink-0 text-info" />
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="text-base font-semibold text-neutral-text">Alamat pengiriman</h3>
                                                    <StatusBadge :status="isAddressReady ? 'aktif' : 'pending'" :label="isAddressReady ? 'Lengkap' : 'Belum lengkap'" />
                                                </div>
                                                <p v-if="props.location?.recipient_name || props.location?.phone" class="mt-2 text-sm font-semibold text-neutral-text">
                                                    {{ [props.location?.recipient_name, props.location?.phone].filter(Boolean).join(' - ') }}
                                                </p>
                                                <p v-if="hasSavedAddress" class="mt-2 text-sm font-semibold text-neutral-text">{{ location.formatted_address }}</p>
                                                <p v-if="hasSavedAddress && location.detail" class="mt-1 text-sm text-neutral-muted">{{ location.detail }}</p>
                                                <p v-if="hasSavedAddress && addressMeta" class="mt-1 text-sm text-neutral-muted">{{ addressMeta }}</p>
                                                <p v-if="hasSavedAddress" class="mt-1 text-xs text-neutral-muted">{{ Number(location.latitude).toFixed(6) }}, {{ Number(location.longitude).toFixed(6) }}</p>
                                                <p v-if="!isAddressReady" class="mt-2 text-sm text-neutral-muted">Lengkapi nama penerima, telepon, detail alamat, dan titik pengiriman agar ongkir bisa dihitung.</p>
                                            </div>
                                        </div>
                                        <AppButton href="/address" variant="secondary">{{ isAddressReady ? 'Ubah Alamat' : 'Lengkapi Alamat' }}</AppButton>
                                    </div>
                                    <p v-if="quoteForm.errors.location || orderForm.errors.location" class="mt-3 text-sm text-danger">{{ quoteForm.errors.location || orderForm.errors.location }}</p>
                                </section>
                            </div>
                        </section>
                    </div>

                    <aside class="h-fit rounded-md border border-neutral-border bg-white p-5">
                        <h2 class="text-lg font-semibold text-neutral-text">Ringkasan pembayaran</h2>

                        <div class="mt-4">
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
                                <span class="font-semibold text-neutral-text">{{ summary.store ? formatRupiah(summary.shipping_cost) : 'Atur alamat' }}</span>
                            </div>
                            <div v-if="summary.voucher" class="flex justify-between gap-3">
                                <span class="text-neutral-muted">Voucher</span>
                                <StatusBadge status="aktif" :label="summary.voucher.code" />
                            </div>
                            <div v-if="summary.store" class="flex justify-between gap-3">
                                <span class="text-neutral-muted">Titik asal</span>
                                <span class="text-right font-semibold text-neutral-text">{{ summary.store.name }}</span>
                            </div>
                            <div v-if="summary.store" class="flex justify-between gap-3">
                                <span class="text-neutral-muted">Jarak alamat</span>
                                <span class="text-right font-semibold text-neutral-text">{{ formatKilometer(summary.store.distance_km) }} km</span>
                            </div>
                            <div class="flex justify-between gap-3 border-t border-neutral-border pt-3 text-base">
                                <span class="font-semibold text-neutral-text">Total</span>
                                <span class="font-bold text-neutral-text">{{ formatRupiah(summary.total) }}</span>
                            </div>
                        </div>

                        <Alert v-if="!canCreateOrder" tone="warning" class="mt-4">
                            Lengkapi data penerima, detail alamat, dan pastikan titik pengiriman masuk area layanan.
                        </Alert>
                        <Alert v-if="orderForm.errors.cart" tone="danger" class="mt-4">{{ orderForm.errors.cart }}</Alert>

                        <div class="mt-5">
                            <AppButton class="w-full" :disabled="!canCreateOrder" :loading="orderForm.processing" @click="submitOrder">
                                Buat Pesanan
                            </AppButton>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
