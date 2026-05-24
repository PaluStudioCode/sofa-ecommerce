<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import { useConfirm } from '@/Composables/useFeedback';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Edit, GripVertical, ImagePlus, Images, Plus, Star, Trash2 } from '@lucide/vue';
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
const imageGroupModalOpen = ref(false);
const managedUploadModalOpen = ref(false);
const selectedImageGroupKey = ref(null);
const draggedManagedImageIndex = ref(null);
const managedImageDropIndex = ref(null);

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
    { key: 'variant_name', label: 'Varian' },
    { key: 'preview_images', label: 'Gambar' },
    { key: 'images_count', label: 'Jumlah' },
    { key: 'primary_image', label: 'Utama' },
];

const imageGroups = computed(() => {
    const groups = new Map();

    props.product.images.forEach((image) => {
        const key = `variant-${image.product_variant_id}`;

        if (!groups.has(key)) {
            groups.set(key, {
                id: key,
                key,
                product_variant_id: image.product_variant_id,
                variant_name: image.variant_name || 'Varian',
                images: [],
            });
        }

        groups.get(key).images.push(image);
    });

    return Array.from(groups.values()).map((group) => ({
        ...group,
        images_count: group.images.length,
        preview_images: group.images.slice(0, 5),
        remaining_images_count: Math.max(0, group.images.length - 5),
        primary_image: group.images.find((image) => image.is_primary) || null,
    }));
});

const selectedImageGroup = computed(() => imageGroups.value.find((group) => group.key === selectedImageGroupKey.value));
const variantIdsWithImages = computed(() => new Set(props.product.images.map((image) => Number(image.product_variant_id))));
const variantsWithoutImages = computed(() => props.product.variants.filter((variant) => !variantIdsWithImages.value.has(Number(variant.id))));
const hasVariantsWithoutImages = computed(() => variantsWithoutImages.value.length > 0);

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

function openImageGroup(group) {
    selectedImageGroupKey.value = group.key;
    imageGroupModalOpen.value = true;
}

function closeImageGroup() {
    imageGroupModalOpen.value = false;
    managedUploadModalOpen.value = false;
    selectedImageGroupKey.value = null;
}

function openManagedUpload() {
    if (!selectedImageGroup.value) {
        return;
    }

    managedUploadModalOpen.value = true;
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

function moveManagedImage(fromIndex, toIndex) {
    const group = selectedImageGroup.value;

    if (!group || fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= group.images.length || toIndex >= group.images.length) {
        return;
    }

    const imageIds = group.images.map((image) => image.id);
    const [imageId] = imageIds.splice(fromIndex, 1);
    imageIds.splice(toIndex, 0, imageId);

    router.put(route('admin.product-images.reorder'), {
        product_id: props.product.id,
        product_variant_id: group.product_variant_id,
        image_ids: imageIds,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

function startManagedImageDrag(event, index) {
    draggedManagedImageIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(index));

    const dragCard = event.currentTarget.closest('[data-managed-image-card]');
    if (dragCard) {
        setManagedImageDragImage(event, dragCard);
    }
}

function setManagedImageDragImage(event, element) {
    const rect = element.getBoundingClientRect();
    const ghost = element.cloneNode(true);

    ghost.style.position = 'fixed';
    ghost.style.top = '-1000px';
    ghost.style.left = '-1000px';
    ghost.style.width = `${rect.width}px`;
    ghost.style.opacity = '1';
    ghost.style.pointerEvents = 'none';
    ghost.style.boxShadow = '0 12px 30px rgba(15, 23, 42, 0.18)';

    document.body.appendChild(ghost);
    event.dataTransfer.setDragImage(ghost, 20, 20);
    setTimeout(() => ghost.remove(), 0);
}

function enterManagedImageDrop(index) {
    managedImageDropIndex.value = index;
}

function dropManagedImage(event, index) {
    const fallbackIndex = Number(event.dataTransfer.getData('text/plain'));
    const fromIndex = draggedManagedImageIndex.value ?? fallbackIndex;

    if (Number.isInteger(fromIndex)) {
        moveManagedImage(fromIndex, index);
    }

    clearManagedImageDrag();
}

function clearManagedImageDrag() {
    draggedManagedImageIndex.value = null;
    managedImageDropIndex.value = null;
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
                </div>
                <AppButton v-if="hasVariantsWithoutImages" type="button" @click="imageModalOpen = true">
                    <ImagePlus class="h-4 w-4" />
                    Upload Gambar
                </AppButton>
            </div>

            <DataTable v-if="product.images.length" :columns="imageColumns" :rows="imageGroups">
                <template #cell-variant_name="{ row }">
                    <div>
                        <p class="font-semibold text-neutral-text">{{ row.variant_name }}</p>
                        <p class="text-xs text-neutral-muted">{{ row.images_count }} gambar</p>
                    </div>
                </template>
                <template #cell-preview_images="{ row }">
                    <div class="flex items-center gap-2">
                        <img v-for="image in row.preview_images" :key="image.id" :src="image.url" :alt="image.alt_text || product.name" class="h-12 w-14 rounded-md object-cover" />
                        <span v-if="row.remaining_images_count" class="inline-flex h-12 w-14 items-center justify-center rounded-md border border-neutral-border bg-neutral-light text-xs font-semibold text-neutral-muted">
                            +{{ row.remaining_images_count }}
                        </span>
                    </div>
                </template>
                <template #cell-images_count="{ value }">{{ value }}</template>
                <template #cell-primary_image="{ value }">
                    <div v-if="value" class="flex items-center gap-2">
                        <img :src="value.url" :alt="value.alt_text || product.name" class="h-10 w-12 rounded-md object-cover" />
                        <Star class="h-5 w-5 fill-yellow-400 text-yellow-500" />
                    </div>
                    <span v-else class="text-sm text-neutral-muted">-</span>
                </template>
                <template #actions="{ row }">
                    <AppButton type="button" variant="secondary" size="sm" @click="openImageGroup(row)">
                        <Images class="h-4 w-4" />
                        Kelola Gambar
                    </AppButton>
                </template>
            </DataTable>
            <EmptyState v-else title="Belum ada gambar produk">
                <template #actions>
                    <AppButton v-if="hasVariantsWithoutImages" type="button" @click="imageModalOpen = true">Upload Gambar</AppButton>
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
        <Modal :show="imageGroupModalOpen" max-width="4xl" @close="closeImageGroup">
            <div class="p-6">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-text">Kelola Gambar</h2>
                        <p class="mt-1 text-sm text-neutral-muted">{{ selectedImageGroup?.variant_name || 'Varian' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <AppButton type="button" @click="openManagedUpload">
                            <ImagePlus class="h-4 w-4" />
                            Tambah Gambar
                        </AppButton>
                        <AppButton type="button" variant="secondary" @click="closeImageGroup">Tutup</AppButton>
                    </div>
                </div>

                <div v-if="selectedImageGroup?.images.length" class="max-h-[60vh] overflow-y-auto rounded-md border border-neutral-border">
                    <div
                        v-for="(image, index) in selectedImageGroup.images"
                        :key="image.id"
                        class="flex flex-col gap-3 border-b p-3 transition last:border-b-0 sm:flex-row sm:items-center sm:justify-between"
                        :class="managedImageDropIndex === index && draggedManagedImageIndex !== index ? 'border-primary-hover bg-primary-soft' : 'border-neutral-border bg-white'"
                        data-managed-image-card
                        @dragover.prevent
                        @dragenter.prevent="enterManagedImageDrop(index)"
                        @drop.prevent="dropManagedImage($event, index)"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                draggable="true"
                                class="inline-grid h-9 w-6 shrink-0 cursor-grab place-items-center rounded text-neutral-muted active:cursor-grabbing"
                                title="Geser"
                                @dragstart="startManagedImageDrag($event, index)"
                                @dragend="clearManagedImageDrag"
                            >
                                <GripVertical class="h-4 w-4" />
                                <span class="sr-only">Geser</span>
                            </span>
                            <img :src="image.url" :alt="image.alt_text || product.name" class="h-16 w-20 shrink-0 rounded-md object-cover" />
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-neutral-text">{{ image.alt_text || product.name }}</p>
                                    <Star v-if="image.is_primary" class="h-4 w-4 shrink-0 fill-yellow-400 text-yellow-500" />
                                </div>
                                <p class="text-xs text-neutral-muted">Urutan {{ index + 1 }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 justify-end gap-2">
                            <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="setPrimaryImage(image)">
                                <Star class="h-4 w-4" :class="image.is_primary ? 'fill-yellow-400 text-yellow-500' : ''" />
                                <span class="sr-only">Jadikan utama</span>
                            </button>
                            <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroyImage(image)">
                                <Trash2 class="h-4 w-4" />
                                <span class="sr-only">Hapus</span>
                            </button>
                        </div>
                    </div>
                </div>

                <EmptyState v-else title="Belum ada gambar" />
            </div>
        </Modal>
        <ProductImageFormModal
            :show="imageModalOpen"
            :product="product"
            :variants="variantsWithoutImages"
            @close="imageModalOpen = false"
        />
        <ProductImageFormModal
            :show="managedUploadModalOpen"
            :product="product"
            :variants="product.variants"
            :fixed-variant-id="selectedImageGroup?.product_variant_id || ''"
            @close="managedUploadModalOpen = false"
        />
    </AuthenticatedLayout>
</template>
