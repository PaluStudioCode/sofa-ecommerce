<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from '@lucide/vue';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    variants: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

const page = usePage();
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
    reserved_stock: 0,
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
        reserved_stock: variant.reserved_stock,
        status: variant.status,
    });
}

function reset() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
}

function submit() {
    if (editingId.value) {
        form.put(route('admin.variants.update', editingId.value), { onSuccess: reset });
        return;
    }

    form.post(route('admin.variants.store'), { onSuccess: reset });
}

function destroyVariant(variant) {
    if (window.confirm(`Hapus varian ${variant.variant_name || variant.sku || variant.id}?`)) {
        router.delete(route('admin.variants.destroy', variant.id));
    }
}
</script>

<template>
    <Head title="Varian dan Stok" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Varian dan Stok">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-neutral-text">Manajemen varian</h2>
            <p v-if="page.props.flash?.success" class="mt-1 text-sm text-success">{{ page.props.flash.success }}</p>
            <p v-if="page.props.flash?.error" class="mt-1 text-sm text-danger">{{ page.props.flash.error }}</p>
        </div>

        <form class="mb-5 rounded-md border border-neutral-border bg-white p-5" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-3">
                <FormSelect id="product_id" v-model="form.product_id" label="Produk" :options="products" :error="form.errors.product_id" required />
                <FormInput id="sku" v-model="form.sku" label="SKU" :error="form.errors.sku" />
                <FormInput id="variant_name" v-model="form.variant_name" label="Nama Varian" :error="form.errors.variant_name" />
                <FormInput id="size" v-model="form.size" label="Ukuran" :error="form.errors.size" />
                <FormInput id="material" v-model="form.material" label="Bahan" :error="form.errors.material" />
                <FormInput id="color" v-model="form.color" label="Warna" :error="form.errors.color" />
                <FormInput id="price" v-model="form.price" type="number" label="Harga" :error="form.errors.price" required />
                <FormInput id="stock" v-model="form.stock" type="number" label="Stok Fisik" :error="form.errors.stock" required />
                <FormInput id="reserved_stock" v-model="form.reserved_stock" type="number" label="Reserved Stock" :error="form.errors.reserved_stock" required />
                <FormSelect id="variant_status" v-model="form.status" label="Status" :options="statuses" :error="form.errors.status" required />
            </div>
            <div class="mt-4 flex gap-2">
                <AppButton type="submit" :loading="form.processing">
                    <Plus class="h-4 w-4" />
                    {{ editingId ? 'Update' : 'Tambah' }}
                </AppButton>
                <AppButton v-if="editingId" type="button" variant="secondary" @click="reset">Batal</AppButton>
            </div>
        </form>

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
    </AuthenticatedLayout>
</template>
