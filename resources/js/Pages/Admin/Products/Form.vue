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
    product: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

const form = useForm({
    category_id: props.product?.category_id || '',
    name: props.product?.name || '',
    slug: props.product?.slug || '',
    description: props.product?.description || '',
    status: props.product?.status || 'aktif',
    is_featured: props.product?.is_featured || false,
});

const title = computed(() => props.product ? 'Edit Produk' : 'Tambah Produk');

function submit() {
    if (props.product) {
        form.put(route('admin.products.update', props.product.id));
        return;
    }

    form.post(route('admin.products.store'));
}
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="title">
        <form class="max-w-3xl rounded-md border border-neutral-border bg-white p-5 shadow-sm" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormSelect id="category_id" v-model="form.category_id" label="Kategori" :options="categories" :error="form.errors.category_id" required />
                <FormSelect id="status" v-model="form.status" label="Status" :options="statuses" :error="form.errors.status" required />
                <FormInput id="name" v-model="form.name" label="Nama Produk" :error="form.errors.name" required />
                <FormInput id="slug" v-model="form.slug" label="Slug" :error="form.errors.slug" placeholder="otomatis jika kosong" />
            </div>

            <label class="mt-4 block" for="description">
                <span class="text-sm font-medium text-neutral-text">Deskripsi<span class="text-danger"> *</span></span>
                <textarea id="description" v-model="form.description" rows="5" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                <p v-if="form.errors.description" class="mt-1 text-sm text-danger">{{ form.errors.description }}</p>
            </label>

            <label class="mt-4 flex items-center gap-2 text-sm font-medium text-neutral-text">
                <input v-model="form.is_featured" type="checkbox" class="rounded border-neutral-border text-primary-hover focus:ring-primary" />
                Produk unggulan
            </label>

            <div class="mt-6 flex flex-wrap gap-2">
                <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
                <AppButton :href="route('admin.products.index')" variant="secondary">Batal</AppButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
