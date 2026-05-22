<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SummaryCard from '@/Components/UI/SummaryCard.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    role: { type: String, default: '' },
    summary: { type: Object, default: () => ({}) },
    recentOrders: { type: Array, default: () => [] },
    period: { type: Object, default: () => ({}) },
});

const orderColumns = [
    { key: 'order_number', label: 'Pesanan' },
    { key: 'customer_name', label: 'Customer' },
    { key: 'total_amount', label: 'Total' },
    { key: 'order_status', label: 'Order' },
    { key: 'payment_status', label: 'Bayar' },
    { key: 'shipment_status', label: 'Kirim' },
];

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Dashboard">
        <div v-if="role === 'owner'" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <SummaryCard title="Total Penjualan" :value="formatRupiah(summary.total_sales || 0)" :meta="`${period.date_from} - ${period.date_to}`" />
            <SummaryCard title="Jumlah Pesanan" :value="summary.orders_count || 0" meta="Pembayaran success" />
            <SummaryCard title="Produk Terjual" :value="summary.products_sold || 0" meta="Snapshot order item" />
            <SummaryCard title="Diskon Voucher" :value="formatRupiah(summary.voucher_discount || 0)" meta="Terpakai pada order" />
            <SummaryCard title="Biaya Pengiriman" :value="formatRupiah(summary.shipping_cost || 0)" meta="Ongkir internal" />
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <SummaryCard title="Pesanan Masuk" :value="summary.incoming_orders || 0" meta="Hari ini" />
            <SummaryCard title="Pembayaran Pending" :value="summary.pending_payments || 0" meta="Perlu dipantau" />
            <SummaryCard title="Pesanan Diproses" :value="summary.processing_orders || 0" meta="Operasional" />
            <SummaryCard title="Pengiriman Berjalan" :value="summary.active_shipments || 0" meta="Internal toko" />
        </div>

        <section class="mt-6">
            <div>
                <div class="mb-3">
                    <h2 class="text-lg font-semibold text-neutral-text">Pesanan terbaru</h2>
                    <p class="text-sm text-neutral-muted">{{ role === 'owner' ? 'Monitoring bisnis terbaru dalam mode baca.' : 'Ringkasan operasional yang perlu dipantau admin.' }}</p>
                </div>
                <DataTable :columns="orderColumns" :rows="recentOrders">
                    <template #cell-total_amount="{ value }">{{ formatRupiah(value) }}</template>
                    <template #cell-order_status="{ value }"><StatusBadge :status="value" /></template>
                    <template #cell-payment_status="{ value }"><StatusBadge :status="value" /></template>
                    <template #cell-shipment_status="{ value }"><StatusBadge :status="value" /></template>
                    <template #empty>
                        <EmptyState title="Belum ada pesanan" />
                    </template>
                </DataTable>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
