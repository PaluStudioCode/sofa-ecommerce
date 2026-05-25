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
                    <article v-for="order in orders.data" :key="order.id" class="rounded-md border border-neutral-border bg-white p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <PackageCheck class="h-5 w-5 text-primary-hover" />
                                    <Link :href="route('orders.show', order.id)" class="font-semibold text-neutral-text hover:text-primary-hover">
                                        {{ order.order_number }}
                                    </Link>
                                </div>
                                <p class="mt-1 text-sm text-neutral-muted">{{ formatDate(order.created_at) }} - {{ order.items_count }} item</p>
                                <p class="mt-2 text-sm text-neutral-muted">{{ order.shipment_summary }}</p>
                                <p class="mt-3 text-xl font-bold text-neutral-text">{{ formatRupiah(order.total_amount) }}</p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-center lg:min-w-[520px]">
                                <div class="flex flex-wrap gap-2">
                                    <StatusBadge :status="order.order_status" />
                                    <StatusBadge :status="order.payment_status" />
                                </div>
                                <AppButton :href="route('orders.show', order.id)" variant="secondary">
                                    <Eye class="h-4 w-4" />
                                    Detail
                                </AppButton>
                            </div>
                        </div>
                    </article>

                    <Pagination :links="orders.links" />
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
