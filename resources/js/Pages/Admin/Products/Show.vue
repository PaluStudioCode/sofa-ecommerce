<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { useConfirm } from '@/Composables/useFeedback';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Edit, ImagePlus, Plus, Star, Trash2 } from '@lucide/vue';
import ProductFormModal from './ProductFormModal.vue';
import ProductImageFormModal from './ProductImageFormModal.vue';
import ProductVariantFormModal from './ProductVariantFormModal.vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    product: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    variantStatuses: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();
const activeTab = ref('info');
const productModalOpen = ref(false);
const variantModalOpen = ref(false);
const imageModalOpen = ref(false);
const selectedVariant = ref(null);

const tabs = [
    { value: 'info', label: 'Info Produk' },
    { value: 'variants', label: 'Varian & Stok' },
    { value: 'images', label: 'Gambar Produk' },
];

const variantColumns = [
    { key: 'variant_name', label: 'Varian' },
    { key: 'specs', label: 'Spesifikasi' },
    { key: 'price', label: 'Harga' },
    { key: 'stock', label: 'Stok' },
    { key: 'status', label: 'Status' },
];

const imageColumns = [
    { key: 'url', label: 'Gambar' },
    { key: 'variant_name', label: 'Varian' },
    { key: 'sort_order', label: 'Urutan' },
    { key: 'is_primary', label: 'Utama' },
];

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}

function openCreateVariant() {
    selectedVariant.value = null;
    variantModalOpen.value = true;
}

function openEditVariant(variant) {
    selectedVariant.value = variant;
    variantModalOpen.value = true;
}

async function destroyVariant(variant) {
    if (await confirm({
        title: 'Hapus varian?',
        message: `Varian ${variant.variant_name || variant.sku || variant.id} akan dihapus dari produk ini.`,
        confirmText: 'Hapus',
        tone: 'danger',
    })) {
        router.delete(route('admin.variants.destroy', variant.id), { preserveScroll: true, preserveState: true });
    }
}

function setPrimaryImage(image) {
    router.put(route('admin.product-images.primary', image.id), {}, { preserveScroll: true, preserveState: true });
}

async function destroyImage(image) {
    if (await confirm({
        title: 'Hapus gambar produk?',
        message: 'Gambar ini akan dihapus permanen dari produk.',
        confirmText: 'Hapus',
        tone: 'danger',
    })) {
        router.delete(route('admin.product-images.destroy', image.id), { preserveScroll: true, preserveState: true });
    }
}
</script>

<template>
    <Head :title="product.name" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="product.name">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-neutral-text">{{ product.name }}</h2>
                <p class="mt-1 text-sm text-neutral-muted">{{ product.category || 'Tanpa kategori' }}</p>
            </div>
            <AppButton :href="route('admin.products.index')" variant="secondary">
                <ArrowLeft class="h-4 w-4" />
                Kembali
            </AppButton>
        </div>

        <Tabs v-model="activeTab" :tabs="tabs" />

        <section v-if="activeTab === 'info'" class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="rounded-md border border-neutral-border bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-neutral-muted">{{ product.category || 'Tanpa kategori' }}</p>
                        <h3 class="text-xl font-semibold text-neutral-text">{{ product.name }}</h3>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <StatusBadge :status="product.status" />
                        <StatusBadge :status="product.is_featured ? 'aktif' : 'nonaktif'" :label="product.is_featured ? 'Unggulan' : 'Reguler'" />
                    </div>
                </div>

                <p class="mt-4 text-sm leading-6 text-neutral-muted">{{ product.description }}</p>

                <div class="mt-5">
                    <AppButton type="button" @click="productModalOpen = true">
                        <Edit class="h-4 w-4" />
                        Edit Produk
                    </AppButton>
                </div>
            </div>

            <div class="rounded-md border border-neutral-border bg-white p-5">
                <h3 class="font-semibold text-neutral-text">Ringkasan</h3>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-neutral-muted">Varian</dt>
                        <dd class="font-semibold text-neutral-text">{{ product.variants.length }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-neutral-muted">Gambar</dt>
                        <dd class="font-semibold text-neutral-text">{{ product.images.length }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-neutral-muted">Stok tersedia</dt>
                        <dd class="font-semibold text-neutral-text">{{ product.available_stock }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-neutral-muted">Harga aktif</dt>
                        <dd class="text-right font-semibold text-neutral-text">
                            {{ product.min_price === product.max_price ? formatRupiah(product.min_price) : `${formatRupiah(product.min_price)} - ${formatRupiah(product.max_price)}` }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <section v-if="activeTab === 'variants'" class="mt-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-neutral-text">Varian & Stok</h3>
                    <p class="mt-1 text-sm text-neutral-muted">Kelola SKU, harga, stok fisik, dan status untuk produk ini.</p>
                </div>
                <AppButton type="button" @click="openCreateVariant">
                    <Plus class="h-4 w-4" />
                    Tambah Varian
                </AppButton>
            </div>

            <DataTable v-if="product.variants.length" :columns="variantColumns" :rows="product.variants">
                <template #cell-variant_name="{ row }">
                    <div>
                        <p class="font-semibold text-neutral-text">{{ row.variant_name || row.sku || `Varian ${row.id}` }}</p>
                        <p class="text-xs text-neutral-muted">{{ row.sku || 'Tanpa SKU' }}</p>
                    </div>
                </template>
                <template #cell-specs="{ row }">
                    {{ [row.size, row.material, row.color].filter(Boolean).join(' / ') || '-' }}
                </template>
                <template #cell-price="{ value }">{{ formatRupiah(value) }}</template>
                <template #cell-stock="{ row }">
                    <div>
                        <p class="font-semibold text-neutral-text">{{ row.stock }}</p>
                        <p class="text-xs text-neutral-muted">Tersedia {{ row.available_stock }} / reserved {{ row.reserved_stock }}</p>
                    </div>
                </template>
                <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
                <template #actions="{ row }">
                    <div class="flex justify-end gap-2">
                        <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="openEditVariant(row)">
                            <Edit class="h-4 w-4" />
                            <span class="sr-only">Edit</span>
                        </button>
                        <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroyVariant(row)">
                            <Trash2 class="h-4 w-4" />
                            <span class="sr-only">Hapus</span>
                        </button>
                    </div>
                </template>
            </DataTable>
            <EmptyState v-else title="Belum ada varian">
                <template #actions>
                    <AppButton type="button" @click="openCreateVariant">Tambah Varian</AppButton>
                </template>
            </EmptyState>
        </section>

        <section v-if="activeTab === 'images'" class="mt-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-neutral-text">Gambar Produk</h3>
                    <p class="mt-1 text-sm text-neutral-muted">Kelola gambar utama, galeri, dan gambar per varian.</p>
                </div>
                <AppButton type="button" @click="imageModalOpen = true">
                    <ImagePlus class="h-4 w-4" />
                    Upload Gambar
                </AppButton>
            </div>

            <DataTable v-if="product.images.length" :columns="imageColumns" :rows="product.images">
                <template #cell-url="{ row }">
                    <img :src="row.url" :alt="row.alt_text || product.name" class="h-14 w-20 rounded-md object-cover" />
                </template>
                <template #cell-variant_name="{ value }">{{ value || 'Tanpa varian' }}</template>
                <template #cell-is_primary="{ value }">
                    <StatusBadge :status="value ? 'aktif' : 'nonaktif'" :label="value ? 'Utama' : 'Reguler'" />
                </template>
                <template #actions="{ row }">
                    <div class="flex justify-end gap-2">
                        <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="setPrimaryImage(row)">
                            <Star class="h-4 w-4" />
                            <span class="sr-only">Jadikan utama</span>
                        </button>
                        <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroyImage(row)">
                            <Trash2 class="h-4 w-4" />
                            <span class="sr-only">Hapus</span>
                        </button>
                    </div>
                </template>
            </DataTable>
            <EmptyState v-else title="Belum ada gambar produk">
                <template #actions>
                    <AppButton type="button" @click="imageModalOpen = true">Upload Gambar</AppButton>
                </template>
            </EmptyState>
        </section>

        <ProductFormModal
            :show="productModalOpen"
            :product="product"
            :categories="categories"
            :statuses="statuses"
            @close="productModalOpen = false"
        />
        <ProductVariantFormModal
            :show="variantModalOpen"
            :product="product"
            :variant="selectedVariant"
            :statuses="variantStatuses"
            @close="variantModalOpen = false"
        />
        <ProductImageFormModal
            :show="imageModalOpen"
            :product="product"
            :variants="product.variants"
            @close="imageModalOpen = false"
        />
    </AuthenticatedLayout>
</template>
