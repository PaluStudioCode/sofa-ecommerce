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
import { useConfirm } from '@/Composables/useFeedback';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, Plus, Trash2 } from '@lucide/vue';
import ProductFormModal from './ProductFormModal.vue';
import { ref } from 'vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();
const formModalOpen = ref(false);
const selectedProduct = ref(null);
const form = useForm({
    keyword: props.filters.keyword || '',
    category: props.filters.category || '',
    status: props.filters.status || '',
});

const columns = [
    { key: 'name', label: 'Produk' },
    { key: 'category', label: 'Kategori' },
    { key: 'status', label: 'Status' },
    { key: 'available_stock', label: 'Stok' },
    { key: 'is_featured', label: 'Unggulan' },
];

const statusOptions = [
    { value: '', label: 'Semua status' },
    { value: 'aktif', label: 'Aktif' },
    { value: 'nonaktif', label: 'Nonaktif' },
];

useAutoFilter(form, ['keyword', 'category', 'status'], 'admin.products.index');

function openCreateModal() {
    selectedProduct.value = null;
    formModalOpen.value = true;
}

async function destroyProduct(product) {
    if (await confirm({
        title: 'Hapus produk?',
        message: `Produk ${product.name} akan dihapus jika belum memiliki data terkait.`,
        confirmText: 'Hapus',
        tone: 'danger',
    })) {
        router.delete(route('admin.products.destroy', product.id));
    }
}
</script>

<template>
    <Head title="Produk" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Produk">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-neutral-text">Manajemen produk</h2>
            </div>
            <AppButton type="button" @click="openCreateModal">
                <Plus class="h-4 w-4" />
                Tambah Produk
            </AppButton>
        </div>

        <form class="mb-4 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[1fr_220px_180px]" @submit.prevent>
            <FormInput id="keyword" v-model="form.keyword" label="Keyword" placeholder="Cari produk" />
            <FormSelect id="category" v-model="form.category" label="Kategori" :options="categories" />
            <FormSelect id="status" v-model="form.status" label="Status" :options="statusOptions" />
        </form>

        <DataTable :columns="columns" :rows="products.data">
            <template #cell-name="{ row }">
                <div class="flex items-center gap-3">
                    <img v-if="row.image_url" :src="row.image_url" :alt="row.name" class="h-10 w-10 rounded-md object-cover" />
                    <div>
                        <p class="font-semibold">{{ row.name }}</p>
                        <p class="text-xs text-neutral-muted">{{ row.variants_count }} varian</p>
                    </div>
                </div>
            </template>
            <template #cell-status="{ value }">
                <StatusBadge :status="value" />
            </template>
            <template #cell-is_featured="{ value }">
                <StatusBadge :status="value ? 'aktif' : 'nonaktif'" :label="value ? 'Ya' : 'Tidak'" />
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <Link :href="route('admin.products.show', row.id)" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light">
                        <Eye class="h-4 w-4" />
                        <span class="sr-only">Detail</span>
                    </Link>
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroyProduct(row)">
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Hapus</span>
                    </button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="Belum ada produk" />
            </template>
        </DataTable>

        <Pagination class="mt-4" :links="products.links" />

        <ProductFormModal
            :show="formModalOpen"
            :product="selectedProduct"
            :categories="categories"
            :statuses="statuses"
            @close="formModalOpen = false"
        />
    </AuthenticatedLayout>
</template>
