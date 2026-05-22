<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    vouchers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    discountTypeOptions: { type: Array, default: () => [] },
});

const page = usePage();
const form = useForm({
    keyword: props.filters.keyword || '',
    status: props.filters.status || '',
    discount_type: props.filters.discount_type || '',
});

const columns = [
    { key: 'code', label: 'Kode' },
    { key: 'name', label: 'Nama' },
    { key: 'discount', label: 'Diskon' },
    { key: 'minimum_purchase', label: 'Min. Belanja' },
    { key: 'usage', label: 'Penggunaan' },
    { key: 'period', label: 'Periode' },
    { key: 'status', label: 'Status' },
];

function submit() {
    form.get(route('admin.vouchers.index'), { preserveState: true, replace: true });
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

function discountLabel(voucher) {
    if (voucher.discount_type === 'percentage') {
        return `${voucher.discount_value}%${voucher.max_discount ? ` maks ${formatRupiah(voucher.max_discount)}` : ''}`;
    }

    return formatRupiah(voucher.discount_value);
}

function usageLabel(voucher) {
    return `${voucher.used_count}${voucher.quota === null ? '' : ` / ${voucher.quota}`}`;
}

function destroyVoucher(voucher) {
    if (window.confirm(`Hapus atau nonaktifkan voucher ${voucher.code}?`)) {
        router.delete(route('admin.vouchers.destroy', voucher.id));
    }
}
</script>

<template>
    <Head title="Voucher" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Voucher">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-neutral-text">Manajemen voucher</h2>
                <p class="mt-1 text-sm text-neutral-muted">Pantau promo, kuota, status, dan penggunaan voucher checkout.</p>
                <p v-if="page.props.flash?.success" class="mt-1 text-sm text-success">{{ page.props.flash.success }}</p>
                <p v-if="page.props.flash?.error" class="mt-1 text-sm text-danger">{{ page.props.flash.error }}</p>
            </div>
            <AppButton :href="route('admin.vouchers.create')">
                <Plus class="h-4 w-4" />
                Tambah Voucher
            </AppButton>
        </div>

        <form class="mb-4 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[1fr_220px_220px_auto]" @submit.prevent="submit">
            <FormInput id="keyword" v-model="form.keyword" label="Keyword" placeholder="Cari kode atau nama" />
            <FormSelect id="status" v-model="form.status" label="Status" :options="statusOptions" />
            <FormSelect id="discount_type" v-model="form.discount_type" label="Tipe diskon" :options="discountTypeOptions" />
            <div class="flex items-end">
                <AppButton type="submit">Filter</AppButton>
            </div>
        </form>

        <DataTable :columns="columns" :rows="vouchers.data">
            <template #cell-code="{ row }">
                <div>
                    <p class="font-semibold text-neutral-text">{{ row.code }}</p>
                    <p class="text-xs text-neutral-muted">{{ row.discount_type === 'percentage' ? 'Persentase' : 'Nominal' }}</p>
                </div>
            </template>
            <template #cell-discount="{ row }">{{ discountLabel(row) }}</template>
            <template #cell-minimum_purchase="{ value }">{{ formatRupiah(value) }}</template>
            <template #cell-usage="{ row }">
                <div>
                    <p class="font-semibold text-neutral-text">{{ usageLabel(row) }}</p>
                    <p class="text-xs text-neutral-muted">{{ row.per_user_limit ? `${row.per_user_limit} / user` : 'Tanpa limit user' }}</p>
                </div>
            </template>
            <template #cell-period="{ row }">
                <div class="text-xs text-neutral-muted">
                    <p>{{ formatDate(row.start_at) }}</p>
                    <p>{{ formatDate(row.end_at) }}</p>
                </div>
            </template>
            <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <Link :href="route('admin.vouchers.edit', row.id)" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light">
                        <Edit class="h-4 w-4" />
                        <span class="sr-only">Edit</span>
                    </Link>
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroyVoucher(row)">
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Hapus</span>
                    </button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="Belum ada voucher" />
            </template>
        </DataTable>

        <Pagination class="mt-4" :links="vouchers.links" />
    </AuthenticatedLayout>
</template>
