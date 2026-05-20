<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SummaryCard from '@/Components/UI/SummaryCard.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
});

const page = usePage();

const columns = [
    { key: 'name', label: 'Area' },
    { key: 'status', label: 'Status' },
    { key: 'owner', label: 'Mode' },
];

const rows = [
    { id: 1, name: 'Pesanan', status: 'pending', owner: page.props.auth.user.role === 'owner' ? 'Baca' : 'Kelola' },
    { id: 2, name: 'Pembayaran', status: 'success', owner: page.props.auth.user.role === 'owner' ? 'Baca' : 'Kelola' },
    { id: 3, name: 'Pengiriman', status: 'dijadwalkan', owner: page.props.auth.user.role === 'owner' ? 'Baca' : 'Kelola' },
];
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Dashboard">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <SummaryCard title="Pesanan Masuk" value="0" meta="Hari ini" />
            <SummaryCard title="Pembayaran Pending" value="0" meta="Perlu dipantau" />
            <SummaryCard title="Pesanan Diproses" value="0" meta="Operasional" />
            <SummaryCard title="Pengiriman Berjalan" value="0" meta="Internal toko" />
        </div>

        <section class="mt-6">
            <DataTable :columns="columns" :rows="rows">
                <template #cell-status="{ value }">
                    <StatusBadge :status="value" />
                </template>
                <template #empty>
                    <EmptyState title="Belum ada data" />
                </template>
            </DataTable>
        </section>
    </AuthenticatedLayout>
</template>
