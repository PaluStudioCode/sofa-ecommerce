<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from '@lucide/vue';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
});

const page = usePage();

const columns = [
    { key: 'sort_order', label: 'Urutan' },
    { key: 'section_key', label: 'Section' },
    { key: 'title', label: 'Judul' },
    { key: 'is_active', label: 'Status' },
];

function destroySection(section) {
    if (window.confirm(`Hapus section ${section.section_key}?`)) {
        router.delete(route('admin.landing-sections.destroy', section.id));
    }
}
</script>

<template>
    <Head title="Landing Page" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Landing Page">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-neutral-text">Konten landing page</h2>
                <p v-if="page.props.flash?.success" class="mt-1 text-sm text-success">{{ page.props.flash.success }}</p>
            </div>
            <AppButton :href="route('admin.landing-sections.create')">
                <Plus class="h-4 w-4" />
                Tambah Section
            </AppButton>
        </div>

        <DataTable :columns="columns" :rows="sections">
            <template #cell-is_active="{ value }">
                <StatusBadge :status="value ? 'aktif' : 'nonaktif'" />
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <Link :href="route('admin.landing-sections.edit', row.id)" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light">
                        <Edit class="h-4 w-4" />
                        <span class="sr-only">Edit</span>
                    </Link>
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroySection(row)">
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Hapus</span>
                    </button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="Belum ada section landing page">
                    <template #actions>
                        <AppButton :href="route('admin.landing-sections.create')">Tambah Section</AppButton>
                    </template>
                </EmptyState>
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>
