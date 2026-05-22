<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import MapPickerShell from '@/Components/UI/MapPickerShell.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head } from '@inertiajs/vue3';
import { MapPinned } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    order: { type: Object, required: true },
});

const itemColumns = [
    { key: 'product_name', label: 'Produk' },
    { key: 'variant_name', label: 'Varian' },
    { key: 'quantity', label: 'Qty' },
    { key: 'product_price', label: 'Harga' },
    { key: 'subtotal', label: 'Subtotal' },
];
const paymentColumns = [
    { key: 'attempt_number', label: 'Attempt' },
    { key: 'midtrans_order_id', label: 'Midtrans Order' },
    { key: 'status', label: 'Status' },
    { key: 'gross_amount', label: 'Gross' },
];
const mapLabel = computed(() => `${props.order.shipping_latitude}, ${props.order.shipping_longitude}`);

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
}

function formatDate(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
</script>

<template>
    <Head :title="`Monitoring ${order.order_number}`" />
    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="order.order_number">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-neutral-text">{{ order.order_number }}</h2>
                <p class="mt-1 text-sm text-neutral-muted">{{ order.customer_name }} - {{ order.customer_phone }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <StatusBadge :status="order.order_status" />
                <StatusBadge :status="order.payment_status" />
                <StatusBadge :status="order.shipment_status" />
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="grid gap-5">
                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="mb-4 font-semibold text-neutral-text">Item pesanan</h3>
                    <DataTable :columns="itemColumns" :rows="order.items">
                        <template #cell-product_price="{ value }">{{ formatRupiah(value) }}</template>
                        <template #cell-subtotal="{ value }">{{ formatRupiah(value) }}</template>
                    </DataTable>
                </section>
                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="mb-4 font-semibold text-neutral-text">Payment attempts</h3>
                    <DataTable :columns="paymentColumns" :rows="order.payments">
                        <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
                        <template #cell-gross_amount="{ value }">{{ formatRupiah(value) }}</template>
                    </DataTable>
                </section>
            </div>

            <aside class="grid h-fit gap-5">
                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Ringkasan</h3>
                    <div class="mt-4 grid gap-3 text-sm">
                        <div class="flex justify-between"><span class="text-neutral-muted">Subtotal</span><span class="font-semibold">{{ formatRupiah(order.subtotal_amount) }}</span></div>
                        <div class="flex justify-between"><span class="text-neutral-muted">Diskon</span><span class="font-semibold">-{{ formatRupiah(order.discount_amount) }}</span></div>
                        <div class="flex justify-between"><span class="text-neutral-muted">Ongkir</span><span class="font-semibold">{{ formatRupiah(order.shipping_cost) }}</span></div>
                        <div class="flex justify-between border-t border-neutral-border pt-3"><span class="font-semibold">Total</span><span class="font-bold">{{ formatRupiah(order.total_amount) }}</span></div>
                    </div>
                </section>
                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Alamat</h3>
                    <div class="mt-3 grid gap-2 text-sm text-neutral-muted">
                        <p class="font-semibold text-neutral-text">{{ order.customer_name }} - {{ order.customer_phone }}</p>
                        <p>{{ order.customer_email }}</p>
                        <p>{{ order.shipping_address }}</p>
                        <p>{{ [order.shipping_city, order.shipping_district, order.shipping_postal_code].filter(Boolean).join(', ') }}</p>
                        <p v-if="order.shipping_note" class="rounded-md bg-neutral-light p-3">{{ order.shipping_note }}</p>
                    </div>
                </section>
                <MapPickerShell title="Lokasi pengiriman" :address="mapLabel">
                    <div class="relative grid h-full min-h-56 w-full place-items-center overflow-hidden rounded-md bg-[#eef4f0]">
                        <div class="absolute inset-0 grid grid-cols-4 grid-rows-4 opacity-40"><span v-for="line in 16" :key="line" class="border border-white/70" /></div>
                        <div class="relative grid h-14 w-14 place-items-center rounded-full border-2 border-info bg-blue-100/80"><MapPinned class="h-7 w-7 text-info" /></div>
                        <div class="absolute bottom-3 left-3 right-3 rounded-md bg-white/90 px-3 py-2 text-xs text-neutral-muted shadow-sm">{{ mapLabel }}</div>
                    </div>
                </MapPickerShell>
                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Pengiriman</h3>
                    <div v-if="order.shipment" class="mt-3 grid gap-2 text-sm text-neutral-muted">
                        <div class="flex justify-between"><span>Status</span><StatusBadge :status="order.shipment.status" /></div>
                        <div class="flex justify-between"><span>Jadwal</span><span>{{ formatDate(order.shipment.scheduled_at) }}</span></div>
                        <div class="flex justify-between"><span>Petugas</span><span>{{ order.shipment.driver_name || '-' }}</span></div>
                    </div>
                    <p v-else class="mt-3 text-sm text-neutral-muted">Shipment belum dibuat.</p>
                </section>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
