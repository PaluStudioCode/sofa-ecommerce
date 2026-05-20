<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    product: { type: Object, required: true },
});

const variantColumns = [
    { key: 'variant_name', label: 'Varian' },
    { key: 'sku', label: 'SKU' },
    { key: 'price', label: 'Harga' },
    { key: 'stock', label: 'Stok' },
    { key: 'reserved_stock', label: 'Reserved' },
    { key: 'status', label: 'Status' },
];

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}
</script>

<template>
    <Head :title="product.name" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="product.name">
        <div class="grid gap-5 lg:grid-cols-[1fr_0.8fr]">
            <section class="rounded-md border border-neutral-border bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-neutral-muted">{{ product.category }}</p>
                        <h2 class="text-xl font-semibold text-neutral-text">{{ product.name }}</h2>
                    </div>
                    <div class="flex gap-2">
                        <StatusBadge :status="product.status" />
                        <StatusBadge :status="product.is_featured ? 'aktif' : 'nonaktif'" :label="product.is_featured ? 'Unggulan' : 'Reguler'" />
                    </div>
                </div>
                <p class="mt-4 text-sm leading-6 text-neutral-muted">{{ product.description }}</p>
                <div class="mt-5 flex gap-2">
                    <AppButton :href="route('admin.products.edit', product.id)">Edit Produk</AppButton>
                    <AppButton :href="route('admin.product-images.index')" variant="secondary">Gambar</AppButton>
                </div>
            </section>

            <section class="rounded-md border border-neutral-border bg-white p-5">
                <h3 class="font-semibold text-neutral-text">Galeri</h3>
                <div v-if="product.images.length" class="mt-3 grid grid-cols-2 gap-3">
                    <img v-for="image in product.images" :key="image.id" :src="image.url" :alt="image.alt_text || product.name" class="aspect-square rounded-md object-cover" />
                </div>
                <p v-else class="mt-3 text-sm text-neutral-muted">Belum ada gambar produk.</p>
            </section>
        </div>

        <section class="mt-5">
            <DataTable :columns="variantColumns" :rows="product.variants">
                <template #cell-price="{ value }">{{ formatRupiah(value) }}</template>
                <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
            </DataTable>
        </section>
    </AuthenticatedLayout>
</template>
