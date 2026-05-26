<script setup>
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import { useConfirm } from '@/Composables/useFeedback';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Trash2, Upload } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    images: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    variants: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();
const formModalOpen = ref(false);
const selectedProduct = ref('');
const form = useForm({
    product_id: '',
    product_variant_id: '',
    image: null,
    alt_text: '',
    sort_order: 0,
});

const columns = [
    { key: 'url', label: 'Gambar' },
    { key: 'product_name', label: 'Produk' },
    { key: 'variant_name', label: 'Varian' },
    { key: 'sort_order', label: 'Urutan' },
];

const variantOptions = computed(() => [
    { value: '', label: selectedProduct.value ? 'Pilih varian' : 'Pilih produk dulu' },
    ...props.variants.filter((variant) => variant.product_id === Number(selectedProduct.value)),
]);

watch(selectedProduct, () => {
    form.product_variant_id = '';
});

function openUploadModal() {
    form.reset();
    form.clearErrors();
    selectedProduct.value = '';
    formModalOpen.value = true;
}

function closeUploadModal() {
    if (!form.processing) {
        formModalOpen.value = false;
        form.reset();
        form.clearErrors();
        selectedProduct.value = '';
    }
}

function submit() {
    form.product_id = selectedProduct.value;
    form.post(route('admin.product-images.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: closeUploadModal,
    });
}

async function destroyImage(image) {
    if (await confirm({
        title: 'Hapus gambar produk?',
        message: `Gambar produk ${image.product_name} akan dihapus permanen.`,
        confirmText: 'Hapus',
        tone: 'danger',
    })) {
        router.delete(route('admin.product-images.destroy', image.id));
    }
}
</script>

<template>
    <Head title="Gambar Produk" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Gambar Produk">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-neutral-text">Manajemen gambar produk</h2>
            <AppButton type="button" @click="openUploadModal">
                <Upload class="h-4 w-4" />
                Upload Gambar
            </AppButton>
        </div>

        <DataTable :columns="columns" :rows="images">
            <template #cell-url="{ row }">
                <img :src="row.url" :alt="row.alt_text || row.product_name" class="h-12 w-16 rounded-md object-cover" />
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroyImage(row)">
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Hapus</span>
                    </button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="Belum ada gambar produk" />
            </template>
        </DataTable>

        <Modal :show="formModalOpen" max-width="2xl" @close="closeUploadModal">
            <form class="p-6" @submit.prevent="submit">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-neutral-text">Upload Gambar Produk</h2>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <FormSelect id="image_product_id" v-model="selectedProduct" label="Produk" :options="products" :error="form.errors.product_id" required />
                    <FormSelect id="image_variant_id" v-model="form.product_variant_id" label="Varian" :options="variantOptions" :error="form.errors.product_variant_id" required />
                    <FormInput id="image_alt_text" v-model="form.alt_text" label="Deskripsi Gambar (Opsional)" placeholder="Contoh: Sofa Luna warna abu-abu tampak depan" :error="form.errors.alt_text" />
                    <FormInput id="image_sort_order" v-model="form.sort_order" type="number" label="Urutan" :error="form.errors.sort_order" required />
                </div>

                <label class="mt-4 block" for="image_file">
                    <span class="text-sm font-medium text-neutral-text">File Gambar<span class="text-danger"> *</span></span>
                    <input id="image_file" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full rounded-md border border-neutral-border text-sm text-neutral-text file:mr-4 file:min-h-10 file:border-0 file:bg-neutral-light file:px-4 file:text-sm file:font-semibold" @input="form.image = $event.target.files[0]" />
                    <p v-if="form.errors.image" class="mt-1 text-sm text-danger">{{ form.errors.image }}</p>
                </label>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <AppButton type="button" variant="secondary" @click="closeUploadModal">Batal</AppButton>
                    <AppButton type="submit" :loading="form.processing">Upload</AppButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
