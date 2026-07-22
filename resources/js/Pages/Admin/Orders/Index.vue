<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { useAutoFilter } from '@/Composables/useAutoFilter';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, Download } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    orderStatusOptions: { type: Array, default: () => [] },
    paymentStatusOptions: { type: Array, default: () => [] },
});

const form = useForm({
    keyword: props.filters.keyword || '',
    order_status: props.filters.order_status || '',
    payment_status: props.filters.payment_status || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const columns = [
    { key: 'order_number', label: 'Order' },
    { key: 'customer_name', label: 'Customer' },
    { key: 'total_amount', label: 'Total' },
    { key: 'payment', label: 'Pembayaran' },
    { key: 'order_status', label: 'Status Order' },
    { key: 'created_at', label: 'Tanggal' },
];

useAutoFilter(form, ['keyword', 'order_status', 'payment_status', 'date_from', 'date_to'], 'admin.orders.index');

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
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-neutral-text">Manajemen pesanan</h2>
            <a :href="route('admin.orders.export', filters)" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-neutral-border bg-white px-4 text-sm font-semibold text-neutral-text transition hover:bg-neutral-light focus:outline-none">
                <Download class="h-4 w-4" />
                Export Laporan (Excel)
            </a>
        </div>

        <form class="mb-4 grid min-w-0 gap-3 rounded-md border border-neutral-border bg-white p-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-[minmax(220px,1.4fr)_repeat(4,minmax(0,1fr))]" @submit.prevent>
            <FormInput id="keyword" v-model="form.keyword" class="sm:col-span-2 xl:col-span-1" label="Keyword" placeholder="Nomor, customer, telepon, email" />
            <FormSelect id="order_status" v-model="form.order_status" label="Order" :options="orderStatusOptions" />
            <FormSelect id="payment_status" v-model="form.payment_status" label="Pembayaran" :options="paymentStatusOptions" />
            <FormInput id="date_from" v-model="form.date_from" type="date" label="Dari" :error="form.errors.date_from" />
            <FormInput id="date_to" v-model="form.date_to" type="date" label="Sampai" :error="form.errors.date_to" />
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
            <template #cell-payment="{ row }">
                <div class="grid gap-1">
                    <StatusBadge :status="row.payment_status" />
                    <p v-if="row.latest_payment" class="text-xs text-neutral-muted">
                        #{{ row.latest_payment.attempt_number }} {{ row.latest_payment.midtrans_order_id }}
                    </p>
                    <p v-else class="text-xs text-neutral-muted">Belum ada attempt</p>
                </div>
            </template>
            <template #cell-order_status="{ value }"><StatusBadge :status="value" /></template>
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
