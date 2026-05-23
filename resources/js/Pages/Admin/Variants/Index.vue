<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { useConfirm } from '@/Composables/useFeedback';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from '@lucide/vue';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    variants: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();
const formModalOpen = ref(false);
const editingId = ref(null);
const form = useForm({
    product_id: '',
    sku: '',
    variant_name: '',
    size: '',
    material: '',
    color: '',
    price: 0,
    stock: 0,
    status: 'aktif',
});

const columns = [
    { key: 'product_name', label: 'Produk' },
    { key: 'variant_name', label: 'Varian' },
    { key: 'price', label: 'Harga' },
    { key: 'stock', label: 'Stok' },
    { key: 'reserved_stock', label: 'Reserved' },
    { key: 'status', label: 'Status' },
];

const formTitle = computed(() => editingId.value ? 'Edit Varian' : 'Tambah Varian');

function openCreateModal() {
    reset();
    formModalOpen.value = true;
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}

function edit(variant) {
    editingId.value = variant.id;
    Object.assign(form, {
        product_id: variant.product_id,
        sku: variant.sku || '',
        variant_name: variant.variant_name || '',
        size: variant.size || '',
        material: variant.material || '',
        color: variant.color || '',
        price: variant.price,
        stock: variant.stock,
        status: variant.status,
    });
    form.clearErrors();
    formModalOpen.value = true;
}

function reset() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
}

function closeModal() {
    if (!form.processing) {
        formModalOpen.value = false;
        reset();
    }
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: closeModal,
    };

    if (editingId.value) {
        form.put(route('admin.variants.update', editingId.value), options);
        return;
    }

    form.post(route('admin.variants.store'), options);
}

async function destroyVariant(variant) {
    if (await confirm({
        title: 'Hapus varian?',
        message: `Varian ${variant.variant_name || variant.sku || variant.id} akan dihapus dari produk.`,
        confirmText: 'Hapus',
        tone: 'danger',
    })) {
        router.delete(route('admin.variants.destroy', variant.id));
    }
}
</script>

<template>
    <Head title="Varian dan Stok" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Varian dan Stok">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-neutral-text">Manajemen varian</h2>
            <AppButton type="button" @click="openCreateModal">
                <Plus class="h-4 w-4" />
                Tambah Varian
            </AppButton>
        </div>

        <DataTable :columns="columns" :rows="variants">
            <template #cell-price="{ value }">{{ formatRupiah(value) }}</template>
            <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="edit(row)">
                        <Edit class="h-4 w-4" />
                        <span class="sr-only">Edit</span>
                    </button>
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroyVariant(row)">
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Hapus</span>
                    </button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="Belum ada varian" />
            </template>
        </DataTable>

        <Modal :show="formModalOpen" max-width="2xl" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-neutral-text">{{ formTitle }}</h2>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <FormSelect id="variant_product_id" v-model="form.product_id" label="Produk" :options="products" :error="form.errors.product_id" required />
                    <FormInput id="variant_sku" v-model="form.sku" label="SKU" :error="form.errors.sku" />
                    <FormInput id="variant_name" v-model="form.variant_name" label="Nama Varian" :error="form.errors.variant_name" />
                    <FormInput id="variant_size" v-model="form.size" label="Ukuran" :error="form.errors.size" />
                    <FormInput id="variant_material" v-model="form.material" label="Bahan" :error="form.errors.material" />
                    <FormInput id="variant_color" v-model="form.color" label="Warna" :error="form.errors.color" />
                    <FormInput id="variant_price" v-model="form.price" type="number" label="Harga" :error="form.errors.price" required />
                    <FormInput id="variant_stock" v-model="form.stock" type="number" label="Stok Fisik" :error="form.errors.stock" required />
                    <FormSelect id="variant_status" v-model="form.status" label="Status" :options="statuses" :error="form.errors.status" required />
                </div>
                <p class="mt-3 text-sm text-neutral-muted">Reserved stock ditampilkan pada tabel dan hanya diubah otomatis oleh sistem checkout/pembayaran.</p>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <AppButton type="button" variant="secondary" @click="closeModal">Batal</AppButton>
                    <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
