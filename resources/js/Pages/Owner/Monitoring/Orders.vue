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
    statusOptions: { type: Array, default: () => [] },
});

const form = useForm({ keyword: props.filters.keyword || '', status: props.filters.status || '' });
const columns = [
    { key: 'order_number', label: 'Order' },
    { key: 'customer_name', label: 'Customer' },
    { key: 'total_amount', label: 'Total' },
    { key: 'order_status', label: 'Order' },
    { key: 'payment_status', label: 'Bayar' },
    { key: 'shipment_status', label: 'Kirim' },
];

function submit() {
    form.get(route('owner.monitoring.orders'), { preserveState: true, replace: true });
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
}
</script>

<template>
    <Head title="Monitoring Pesanan" />
    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Monitoring Pesanan">
        <form class="mb-4 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[1fr_220px_auto]" @submit.prevent="submit">
            <FormInput id="keyword" v-model="form.keyword" label="Keyword" placeholder="Nomor, customer, telepon, email" />
            <FormSelect id="status" v-model="form.status" label="Status order" :options="statusOptions" />
            <div class="flex items-end"><AppButton type="submit">Filter</AppButton></div>
        </form>
        <DataTable :columns="columns" :rows="orders.data">
            <template #cell-order_number="{ row }"><Link :href="route('owner.monitoring.orders.show', row.id)" class="font-semibold text-neutral-text hover:text-primary-hover">{{ row.order_number }}</Link></template>
            <template #cell-customer_name="{ row }"><div><p class="font-semibold">{{ row.customer_name }}</p><p class="text-xs text-neutral-muted">{{ row.customer_phone }}</p></div></template>
            <template #cell-total_amount="{ value }">{{ formatRupiah(value) }}</template>
            <template #cell-order_status="{ value }"><StatusBadge :status="value" /></template>
            <template #cell-payment_status="{ value }"><StatusBadge :status="value" /></template>
            <template #cell-shipment_status="{ value }"><StatusBadge :status="value" /></template>
            <template #actions="{ row }"><AppButton :href="route('owner.monitoring.orders.show', row.id)" variant="secondary" size="sm"><Eye class="h-4 w-4" />Detail</AppButton></template>
            <template #empty><EmptyState title="Belum ada pesanan" /></template>
        </DataTable>
        <Pagination class="mt-4" :links="orders.links" />
    </AuthenticatedLayout>
</template>
