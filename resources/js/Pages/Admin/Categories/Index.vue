<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { useConfirm } from '@/Composables/useFeedback';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from '@lucide/vue';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();
const formModalOpen = ref(false);
const editingId = ref(null);
const form = useForm({
    name: '',
    slug: '',
    description: '',
    is_active: true,
});

const columns = [
    { key: 'name', label: 'Nama' },
    { key: 'slug', label: 'Slug' },
    { key: 'products_count', label: 'Produk' },
    { key: 'is_active', label: 'Status' },
];

const formTitle = computed(() => editingId.value ? 'Edit Kategori' : 'Tambah Kategori');

function openCreateModal() {
    reset();
    formModalOpen.value = true;
}

function edit(category) {
    editingId.value = category.id;
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description || '';
    form.is_active = category.is_active;
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
        form.put(route('admin.categories.update', editingId.value), options);
        return;
    }

    form.post(route('admin.categories.store'), options);
}

async function destroyCategory(category) {
    if (await confirm({
        title: 'Hapus kategori?',
        message: `Kategori ${category.name} akan dihapus jika belum dipakai produk.`,
        confirmText: 'Hapus',
        tone: 'danger',
    })) {
        router.delete(route('admin.categories.destroy', category.id));
    }
}
</script>

<template>
    <Head title="Kategori" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Kategori">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-neutral-text">Manajemen kategori</h2>
            <AppButton type="button" @click="openCreateModal">
                <Plus class="h-4 w-4" />
                Tambah Kategori
            </AppButton>
        </div>

        <DataTable :columns="columns" :rows="categories">
            <template #cell-is_active="{ value }">
                <StatusBadge :status="value ? 'aktif' : 'nonaktif'" />
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="edit(row)">
                        <Edit class="h-4 w-4" />
                        <span class="sr-only">Edit</span>
                    </button>
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroyCategory(row)">
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Hapus</span>
                    </button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="Belum ada kategori" />
            </template>
        </DataTable>

        <Modal :show="formModalOpen" max-width="2xl" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-neutral-text">{{ formTitle }}</h2>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <FormInput id="category_name" v-model="form.name" label="Nama Kategori" :error="form.errors.name" required />
                    <FormInput id="category_slug" v-model="form.slug" label="Slug" :error="form.errors.slug" placeholder="otomatis jika kosong" />
                    <label class="block md:col-span-2" for="category_description">
                        <span class="text-sm font-medium text-neutral-text">Deskripsi</span>
                        <textarea id="category_description" v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                        <p v-if="form.errors.description" class="mt-1 text-sm text-danger">{{ form.errors.description }}</p>
                    </label>
                </div>
                <label class="mt-4 flex items-center gap-2 text-sm font-medium text-neutral-text">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-neutral-border text-primary-hover focus:ring-primary" />
                    Aktif
                </label>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <AppButton type="button" variant="secondary" @click="closeModal">Batal</AppButton>
                    <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
