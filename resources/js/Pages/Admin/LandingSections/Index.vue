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

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    sectionKeys: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();
const formModalOpen = ref(false);
const editingSection = ref(null);
const form = useForm({
    section_key: 'hero',
    title: '',
    subtitle: '',
    content: '',
    image: null,
    button_label: '',
    button_url: '',
    sort_order: 0,
    is_active: true,
});

const columns = [
    { key: 'sort_order', label: 'Urutan' },
    { key: 'section_key', label: 'Section' },
    { key: 'title', label: 'Judul' },
    { key: 'is_active', label: 'Status' },
];

const formTitle = computed(() => editingSection.value ? 'Edit Section' : 'Tambah Section');

function fillForm(section = null) {
    editingSection.value = section;
    form.transform((data) => data);
    form.defaults({
        section_key: section?.section_key || props.sectionKeys[0]?.value || 'hero',
        title: section?.title || '',
        subtitle: section?.subtitle || '',
        content: section?.content || '',
        image: null,
        button_label: section?.button_label || '',
        button_url: section?.button_url || '',
        sort_order: section?.sort_order ?? 0,
        is_active: section?.is_active ?? true,
    });
    form.reset();
    form.clearErrors();
}

function openCreateModal() {
    fillForm();
    formModalOpen.value = true;
}

function openEditModal(section) {
    fillForm(section);
    formModalOpen.value = true;
}

function closeModal() {
    if (!form.processing) {
        formModalOpen.value = false;
        fillForm();
    }
}

function submit() {
    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: closeModal,
    };

    if (editingSection.value) {
        form.transform((data) => ({ ...data, _method: 'put' }))
            .post(route('admin.landing-sections.update', editingSection.value.id), options);
        return;
    }

    form.post(route('admin.landing-sections.store'), options);
}

async function destroySection(section) {
    if (await confirm({
        title: 'Hapus section?',
        message: `Section ${section.section_key} akan dihapus dari landing page.`,
        confirmText: 'Hapus',
        tone: 'danger',
    })) {
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
            </div>
            <AppButton type="button" @click="openCreateModal">
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
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="openEditModal(row)">
                        <Edit class="h-4 w-4" />
                        <span class="sr-only">Edit</span>
                    </button>
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroySection(row)">
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Hapus</span>
                    </button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="Belum ada section landing page">
                    <template #actions>
                        <AppButton type="button" @click="openCreateModal">Tambah Section</AppButton>
                    </template>
                </EmptyState>
            </template>
        </DataTable>

        <Modal :show="formModalOpen" max-width="2xl" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-neutral-text">{{ formTitle }}</h2>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <FormSelect id="section_key" v-model="form.section_key" label="Section" :options="sectionKeys" :error="form.errors.section_key" required />
                    <FormInput id="section_sort_order" v-model="form.sort_order" type="number" label="Urutan" :error="form.errors.sort_order" required />
                    <FormInput id="section_title" v-model="form.title" label="Judul" :error="form.errors.title" />
                    <FormInput id="section_subtitle" v-model="form.subtitle" label="Subjudul" :error="form.errors.subtitle" />
                    <FormInput id="section_button_label" v-model="form.button_label" label="Label Tombol" :error="form.errors.button_label" />
                    <FormInput id="section_button_url" v-model="form.button_url" label="URL Tombol" :error="form.errors.button_url" placeholder="/catalog" />
                </div>

                <label class="mt-4 block" for="section_content">
                    <span class="text-sm font-medium text-neutral-text">Konten</span>
                    <textarea id="section_content" v-model="form.content" rows="4" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                    <p v-if="form.errors.content" class="mt-1 text-sm text-danger">{{ form.errors.content }}</p>
                </label>

                <label class="mt-4 block" for="section_image">
                    <span class="text-sm font-medium text-neutral-text">Gambar</span>
                    <input id="section_image" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full rounded-md border border-neutral-border text-sm text-neutral-text file:mr-4 file:min-h-10 file:border-0 file:bg-neutral-light file:px-4 file:text-sm file:font-semibold" @input="form.image = $event.target.files[0]" />
                    <p v-if="editingSection?.image_url" class="mt-1 text-sm text-neutral-muted">Gambar aktif tersimpan.</p>
                    <p v-if="form.errors.image" class="mt-1 text-sm text-danger">{{ form.errors.image }}</p>
                </label>

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
