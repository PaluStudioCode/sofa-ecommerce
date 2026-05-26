<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Eye, PackageCheck } from '@lucide/vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';

defineProps({
    orders: { type: Object, required: true },
});

const sofaFallback = 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80';

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
</script>

<template>
    <Head title="Riwayat Pesanan" />

    <PublicLayout>
        <section class="bg-neutral-light py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-neutral-text">Riwayat Pesanan</h1>
                        <p class="mt-1 text-sm text-neutral-muted">Pantau pembayaran, proses pesanan, dan pengiriman internal toko.</p>
                    </div>
                    <AppButton href="/catalog">Belanja Lagi</AppButton>
                </div>

                <EmptyState v-if="orders.data.length === 0" title="Belum ada pesanan" message="Pesanan yang kamu buat akan tampil di sini.">
                    <template #actions>
                        <AppButton href="/catalog">Lihat Katalog</AppButton>
                    </template>
                </EmptyState>

                <div v-else class="grid gap-4">
                    <div class="max-h-[68vh] overflow-y-auto pr-1">
                        <div class="grid gap-3">
                            <article v-for="order in orders.data" :key="order.id" class="rounded-md border border-neutral-border bg-white p-4">
                                <div class="grid gap-3 sm:grid-cols-[76px_minmax(0,1fr)] lg:grid-cols-[76px_minmax(0,1fr)_auto] lg:items-center">
                                    <Link :href="route('orders.show', order.id)" class="block overflow-hidden rounded-md border border-neutral-border bg-neutral-light">
                                        <img :src="order.image_url || sofaFallback" :alt="order.preview_product_name || order.order_number" class="aspect-square w-full object-cover" />
                                    </Link>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <PackageCheck class="h-5 w-5 shrink-0 text-primary-hover" />
                                            <Link :href="route('orders.show', order.id)" class="font-semibold text-neutral-text hover:text-primary-hover">
                                                {{ order.order_number }}
                                            </Link>
                                            <span class="text-sm font-semibold text-neutral-text">{{ formatRupiah(order.total_amount) }}</span>
                                        </div>
                                        <p v-if="order.preview_product_name" class="mt-1 truncate text-sm font-medium text-neutral-text">
                                            {{ order.preview_product_name }}<span v-if="order.preview_variant_name" class="font-normal text-neutral-muted"> - {{ order.preview_variant_name }}</span>
                                        </p>
                                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-neutral-muted">
                                            <span>{{ formatDate(order.created_at) }}</span>
                                            <span>{{ order.items_count }} item</span>
                                            <span>{{ order.shipment_summary }}</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:justify-end">
                                        <div class="flex flex-wrap gap-2">
                                            <StatusBadge :status="order.order_status" />
                                            <StatusBadge :status="order.payment_status" />
                                        </div>
                                        <AppButton :href="route('orders.show', order.id)" variant="secondary" size="sm">
                                            <Eye class="h-4 w-4" />
                                            Detail
                                        </AppButton>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <Pagination :links="orders.links" />
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
