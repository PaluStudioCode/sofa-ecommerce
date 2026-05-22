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
    shipments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
});

const form = useForm({ keyword: props.filters.keyword || '', status: props.filters.status || '' });
const columns = [
    { key: 'order', label: 'Order' },
    { key: 'status', label: 'Status' },
    { key: 'scheduled_at', label: 'Jadwal' },
    { key: 'driver_name', label: 'Petugas' },
    { key: 'vehicle_note', label: 'Kendaraan' },
];

function submit() { form.get(route('owner.monitoring.shipments'), { preserveState: true, replace: true }); }
function formatDate(value) { if (!value) return '-'; return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)); }
</script>

<template>
    <Head title="Monitoring Pengiriman" />
    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Monitoring Pengiriman">
        <form class="mb-4 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[1fr_220px_auto]" @submit.prevent="submit">
            <FormInput id="keyword" v-model="form.keyword" label="Keyword" placeholder="Order, customer, petugas" />
            <FormSelect id="status" v-model="form.status" label="Status" :options="statusOptions" />
            <div class="flex items-end"><AppButton type="submit">Filter</AppButton></div>
        </form>
        <DataTable :columns="columns" :rows="shipments.data">
            <template #cell-order="{ row }">
                <div v-if="row.order">
                    <Link :href="route('owner.monitoring.orders.show', row.order.id)" class="font-semibold text-neutral-text hover:text-primary-hover">{{ row.order.order_number }}</Link>
                    <p class="text-xs text-neutral-muted">{{ row.order.customer_name }}</p>
                </div>
            </template>
            <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
            <template #cell-scheduled_at="{ value }">{{ formatDate(value) }}</template>
            <template #cell-driver_name="{ value }">{{ value || '-' }}</template>
            <template #cell-vehicle_note="{ value }">{{ value || '-' }}</template>
            <template #empty><EmptyState title="Belum ada pengiriman" /></template>
        </DataTable>
        <Pagination class="mt-4" :links="shipments.links" />
    </AuthenticatedLayout>
</template>
