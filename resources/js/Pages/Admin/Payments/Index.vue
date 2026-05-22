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
    payments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
});

const form = useForm({
    keyword: props.filters.keyword || '',
    status: props.filters.status || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const columns = [
    { key: 'midtrans_order_id', label: 'Midtrans Order' },
    { key: 'order', label: 'Order' },
    { key: 'status', label: 'Status' },
    { key: 'payment_type', label: 'Metode' },
    { key: 'gross_amount', label: 'Gross' },
    { key: 'created_at', label: 'Tanggal' },
];

function submit() {
    form.get(route('admin.payments.index'), { preserveState: true, replace: true });
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
    <Head title="Pembayaran" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Pembayaran">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-neutral-text">Daftar pembayaran</h2>
            <p class="mt-1 text-sm text-neutral-muted">Payment attempt Midtrans ditampilkan untuk monitoring, bukan untuk membuat status sukses manual.</p>
        </div>

        <form class="mb-4 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[1fr_180px_150px_150px_auto]" @submit.prevent="submit">
            <FormInput id="keyword" v-model="form.keyword" label="Keyword" placeholder="Order, customer, Midtrans ID" />
            <FormSelect id="status" v-model="form.status" label="Status" :options="statusOptions" />
            <FormInput id="date_from" v-model="form.date_from" type="date" label="Dari" :error="form.errors.date_from" />
            <FormInput id="date_to" v-model="form.date_to" type="date" label="Sampai" :error="form.errors.date_to" />
            <div class="flex items-end">
                <AppButton type="submit">Filter</AppButton>
            </div>
        </form>

        <DataTable :columns="columns" :rows="payments.data">
            <template #cell-midtrans_order_id="{ row }">
                <Link :href="route('admin.payments.show', row.id)" class="font-semibold text-neutral-text hover:text-primary-hover">
                    {{ row.midtrans_order_id }}
                </Link>
                <p class="text-xs text-neutral-muted">Attempt #{{ row.attempt_number }}</p>
            </template>
            <template #cell-order="{ row }">
                <div v-if="row.order">
                    <Link :href="route('admin.orders.show', row.order.id)" class="font-semibold text-neutral-text hover:text-primary-hover">
                        {{ row.order.order_number }}
                    </Link>
                    <p class="text-xs text-neutral-muted">{{ row.order.customer_name }}</p>
                </div>
            </template>
            <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
            <template #cell-payment_type="{ value }">{{ value || '-' }}</template>
            <template #cell-gross_amount="{ value }">{{ formatRupiah(value) }}</template>
            <template #cell-created_at="{ value }">{{ formatDate(value) }}</template>
            <template #actions="{ row }">
                <AppButton :href="route('admin.payments.show', row.id)" variant="secondary" size="sm">
                    <Eye class="h-4 w-4" />
                    Detail
                </AppButton>
            </template>
            <template #empty>
                <EmptyState title="Belum ada payment attempt" />
            </template>
        </DataTable>

        <Pagination class="mt-4" :links="payments.links" />
    </AuthenticatedLayout>
</template>
