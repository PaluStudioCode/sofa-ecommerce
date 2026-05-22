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
    { key: 'code', label: 'Kode' },
    { key: 'name', label: 'Nama' },
    { key: 'usage_count', label: 'Penggunaan' },
    { key: 'discount_total', label: 'Total Diskon' },
];

function submit() {
    form.get(route('owner.reports.vouchers'), { preserveState: true, replace: true });
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
}
</script>

<template>
    <Head title="Laporan Voucher" />
    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Laporan Voucher">
        <form class="mb-5 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[180px_180px_auto]" @submit.prevent="submit">
            <FormInput id="date_from" v-model="form.date_from" type="date" label="Dari" :error="form.errors.date_from" />
            <FormInput id="date_to" v-model="form.date_to" type="date" label="Sampai" :error="form.errors.date_to" />
            <div class="flex items-end"><AppButton type="submit">Terapkan</AppButton></div>
        </form>
        <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <SummaryCard title="Total Diskon Voucher" :value="formatRupiah(summary.voucher_discount)" meta="Snapshot order" />
            <SummaryCard title="Jumlah Pesanan" :value="summary.orders_count" meta="Order berhasil" />
            <SummaryCard title="Total Penjualan" :value="formatRupiah(summary.total_sales)" meta="Setelah diskon dan ongkir" />
        </div>
        <DataTable :columns="columns" :rows="rows">
            <template #cell-code="{ value }"><span class="font-semibold text-neutral-text">{{ value }}</span></template>
            <template #cell-discount_total="{ value }">{{ formatRupiah(value) }}</template>
            <template #empty><EmptyState title="Belum ada penggunaan voucher" /></template>
        </DataTable>
    </AuthenticatedLayout>
</template>
