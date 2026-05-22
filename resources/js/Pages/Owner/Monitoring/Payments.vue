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

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    payments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
});

const form = useForm({ keyword: props.filters.keyword || '', status: props.filters.status || '' });
const columns = [
    { key: 'midtrans_order_id', label: 'Midtrans Order' },
    { key: 'order', label: 'Order' },
    { key: 'status', label: 'Status' },
    { key: 'payment_type', label: 'Metode' },
    { key: 'gross_amount', label: 'Gross' },
];

function submit() { form.get(route('owner.monitoring.payments'), { preserveState: true, replace: true }); }
function formatRupiah(value) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0); }
</script>

<template>
    <Head title="Monitoring Pembayaran" />
    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Monitoring Pembayaran">
        <form class="mb-4 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[1fr_220px_auto]" @submit.prevent="submit">
            <FormInput id="keyword" v-model="form.keyword" label="Keyword" placeholder="Order, customer, Midtrans ID" />
            <FormSelect id="status" v-model="form.status" label="Status" :options="statusOptions" />
            <div class="flex items-end"><AppButton type="submit">Filter</AppButton></div>
        </form>
        <DataTable :columns="columns" :rows="payments.data">
            <template #cell-order="{ row }"><Link v-if="row.order" :href="route('owner.monitoring.orders.show', row.order.id)" class="font-semibold text-neutral-text hover:text-primary-hover">{{ row.order.order_number }}</Link></template>
            <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
            <template #cell-payment_type="{ value }">{{ value || '-' }}</template>
            <template #cell-gross_amount="{ value }">{{ formatRupiah(value) }}</template>
            <template #empty><EmptyState title="Belum ada payment attempt" /></template>
        </DataTable>
        <Pagination class="mt-4" :links="payments.links" />
    </AuthenticatedLayout>
</template>
