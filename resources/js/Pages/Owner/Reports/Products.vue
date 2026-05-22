<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import SummaryCard from '@/Components/UI/SummaryCard.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    period: { type: Object, required: true },
    summary: { type: Object, required: true },
    rows: { type: Array, default: () => [] },
});

const form = useForm({ date_from: props.period.date_from, date_to: props.period.date_to });
const columns = [
    { key: 'product_name', label: 'Produk' },
    { key: 'variant_name', label: 'Varian' },
    { key: 'variant_sku', label: 'SKU' },
    { key: 'quantity_sold', label: 'Terjual' },
    { key: 'gross_sales', label: 'Subtotal' },
];

function submit() {
    form.get(route('owner.reports.products'), { preserveState: true, replace: true });
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
}
</script>

<template>
    <Head title="Produk Terjual" />
    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Produk Terjual">
        <form class="mb-5 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[180px_180px_auto]" @submit.prevent="submit">
            <FormInput id="date_from" v-model="form.date_from" type="date" label="Dari" :error="form.errors.date_from" />
            <FormInput id="date_to" v-model="form.date_to" type="date" label="Sampai" :error="form.errors.date_to" />
            <div class="flex items-end"><AppButton type="submit">Terapkan</AppButton></div>
        </form>
        <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <SummaryCard title="Produk Terjual" :value="summary.products_sold" meta="Unit dalam periode" />
            <SummaryCard title="Total Penjualan" :value="formatRupiah(summary.total_sales)" meta="Payment success" />
            <SummaryCard title="Jumlah Pesanan" :value="summary.orders_count" meta="Order berhasil" />
        </div>
        <DataTable :columns="columns" :rows="rows">
            <template #cell-gross_sales="{ value }">{{ formatRupiah(value) }}</template>
            <template #empty><EmptyState title="Belum ada produk terjual" /></template>
        </DataTable>
    </AuthenticatedLayout>
</template>
