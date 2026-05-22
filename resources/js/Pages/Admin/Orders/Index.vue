<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    orderStatusOptions: { type: Array, default: () => [] },
    paymentStatusOptions: { type: Array, default: () => [] },
    shipmentStatusOptions: { type: Array, default: () => [] },
});

const form = useForm({
    keyword: props.filters.keyword || '',
    order_status: props.filters.order_status || '',
    payment_status: props.filters.payment_status || '',
    shipment_status: props.filters.shipment_status || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const columns = [
    { key: 'order_number', label: 'Order' },
    { key: 'customer_name', label: 'Customer' },
    { key: 'total_amount', label: 'Total' },
    { key: 'order_status', label: 'Order' },
    { key: 'payment_status', label: 'Bayar' },
    { key: 'shipment_status', label: 'Kirim' },
    { key: 'created_at', label: 'Tanggal' },
];

function submit() {
    form.get(route('admin.orders.index'), { preserveState: true, replace: true });
}

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
    <Head title="Pesanan" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Pesanan">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-neutral-text">Manajemen pesanan</h2>
            <p class="mt-1 text-sm text-neutral-muted">Filter pesanan, pantau pembayaran, dan proses status order sesuai transisi PRD.</p>
        </div>

        <form class="mb-4 grid gap-3 rounded-md border border-neutral-border bg-white p-4 lg:grid-cols-[1.2fr_repeat(3,180px)_150px_150px_auto]" @submit.prevent="submit">
            <FormInput id="keyword" v-model="form.keyword" label="Keyword" placeholder="Nomor, customer, telepon, email" />
            <FormSelect id="order_status" v-model="form.order_status" label="Order" :options="orderStatusOptions" />
            <FormSelect id="payment_status" v-model="form.payment_status" label="Pembayaran" :options="paymentStatusOptions" />
            <FormSelect id="shipment_status" v-model="form.shipment_status" label="Pengiriman" :options="shipmentStatusOptions" />
            <FormInput id="date_from" v-model="form.date_from" type="date" label="Dari" :error="form.errors.date_from" />
            <FormInput id="date_to" v-model="form.date_to" type="date" label="Sampai" :error="form.errors.date_to" />
            <div class="flex items-end">
                <AppButton type="submit">Filter</AppButton>
            </div>
        </form>

        <DataTable :columns="columns" :rows="orders.data">
            <template #cell-order_number="{ row }">
                <div>
                    <Link :href="route('admin.orders.show', row.id)" class="font-semibold text-neutral-text hover:text-primary-hover">
                        {{ row.order_number }}
                    </Link>
                    <p class="text-xs text-neutral-muted">{{ row.items_count }} item</p>
                </div>
            </template>
            <template #cell-customer_name="{ row }">
                <div>
                    <p class="font-semibold text-neutral-text">{{ row.customer_name }}</p>
                    <p class="text-xs text-neutral-muted">{{ row.customer_email || row.customer_phone }}</p>
                </div>
            </template>
            <template #cell-total_amount="{ value }">{{ formatRupiah(value) }}</template>
            <template #cell-order_status="{ value }"><StatusBadge :status="value" /></template>
            <template #cell-payment_status="{ value }"><StatusBadge :status="value" /></template>
            <template #cell-shipment_status="{ value }"><StatusBadge :status="value" /></template>
            <template #cell-created_at="{ value }">{{ formatDate(value) }}</template>
            <template #actions="{ row }">
                <AppButton :href="route('admin.orders.show', row.id)" variant="secondary" size="sm">
                    <Eye class="h-4 w-4" />
                    Detail
                </AppButton>
            </template>
            <template #empty>
                <EmptyState title="Belum ada pesanan" />
            </template>
        </DataTable>

        <Pagination class="mt-4" :links="orders.links" />
    </AuthenticatedLayout>
</template>
