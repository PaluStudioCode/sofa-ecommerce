<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    section: { type: Object, default: null },
    sectionKeys: { type: Array, default: () => [] },
});

const form = useForm({
    section_key: props.section?.section_key || 'hero',
    title: props.section?.title || '',
    subtitle: props.section?.subtitle || '',
    content: props.section?.content || '',
    image: null,
    button_label: props.section?.button_label || '',
    button_url: props.section?.button_url || '',
    sort_order: props.section?.sort_order ?? 0,
    is_active: props.section?.is_active ?? true,
});

const title = computed(() => props.section ? 'Edit Section' : 'Tambah Section');

function submit() {
    const options = { forceFormData: true };

    if (props.section) {
        form.transform((data) => ({ ...data, _method: 'put' }))
            .post(route('admin.landing-sections.update', props.section.id), options);
        return;
    }

    form.post(route('admin.landing-sections.store'), options);
}
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="title">
        <form class="max-w-3xl rounded-md border border-neutral-border bg-white p-5 shadow-sm" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormSelect id="section_key" v-model="form.section_key" label="Section" :options="sectionKeys" :error="form.errors.section_key" required />
                <FormInput id="sort_order" v-model="form.sort_order" type="number" label="Urutan" :error="form.errors.sort_order" required />
                <FormInput id="title" v-model="form.title" label="Judul" :error="form.errors.title" />
                <FormInput id="subtitle" v-model="form.subtitle" label="Subjudul" :error="form.errors.subtitle" />
                <FormInput id="button_label" v-model="form.button_label" label="Label Tombol" :error="form.errors.button_label" />
                <FormInput id="button_url" v-model="form.button_url" label="URL Tombol" :error="form.errors.button_url" placeholder="/catalog" />
            </div>

            <label class="mt-4 block" for="content">
                <span class="text-sm font-medium text-neutral-text">Konten</span>
                <textarea id="content" v-model="form.content" rows="4" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                <p v-if="form.errors.content" class="mt-1 text-sm text-danger">{{ form.errors.content }}</p>
            </label>

            <label class="mt-4 block" for="image">
                <span class="text-sm font-medium text-neutral-text">Gambar</span>
                <input id="image" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full rounded-md border border-neutral-border text-sm text-neutral-text file:mr-4 file:min-h-10 file:border-0 file:bg-neutral-light file:px-4 file:text-sm file:font-semibold" @input="form.image = $event.target.files[0]" />
                <p v-if="section?.image_url" class="mt-1 text-sm text-neutral-muted">Gambar aktif tersimpan.</p>
                <p v-if="form.errors.image" class="mt-1 text-sm text-danger">{{ form.errors.image }}</p>
            </label>

            <label class="mt-4 flex items-center gap-2 text-sm font-medium text-neutral-text">
                <input v-model="form.is_active" type="checkbox" class="rounded border-neutral-border text-primary-hover focus:ring-primary" />
                Aktif
            </label>

            <div class="mt-6 flex flex-wrap gap-2">
                <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
                <AppButton :href="route('admin.landing-sections.index')" variant="secondary">Batal</AppButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
