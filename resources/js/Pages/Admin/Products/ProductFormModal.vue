<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    product: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
    category_id: '',
    name: '',
    slug: '',
    description: '',
    status: 'aktif',
    is_featured: false,
});

const title = computed(() => props.product ? 'Edit Produk' : 'Tambah Produk');

function fillForm() {
    form.defaults({
        category_id: props.product?.category_id || '',
        name: props.product?.name || '',
        slug: props.product?.slug || '',
        description: props.product?.description || '',
        status: props.product?.status || 'aktif',
        is_featured: props.product?.is_featured || false,
    });
    form.reset();
    form.clearErrors();
}

watch(
    () => [props.show, props.product],
    () => {
        if (props.show) {
            fillForm();
        }
    },
    { immediate: true },
);

function close() {
    if (!form.processing) {
        emit('close');
    }
}

function submit() {
    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => emit('close'),
    };

    if (props.product) {
        form.put(route('admin.products.update', props.product.id), options);
        return;
    }

    form.post(route('admin.products.store'), options);
}
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-neutral-text">{{ title }}</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <FormSelect id="product_category_id" v-model="form.category_id" label="Kategori" :options="categories" :error="form.errors.category_id" required />
                <FormSelect id="product_status" v-model="form.status" label="Status" :options="statuses" :error="form.errors.status" required />
                <FormInput id="product_name" v-model="form.name" label="Nama Produk" :error="form.errors.name" required />
                <FormInput id="product_slug" v-model="form.slug" label="Slug" :error="form.errors.slug" placeholder="otomatis jika kosong" />
            </div>

            <label class="mt-4 block" for="product_description">
                <span class="text-sm font-medium text-neutral-text">Deskripsi<span class="text-danger"> *</span></span>
                <textarea id="product_description" v-model="form.description" rows="5" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                <p v-if="form.errors.description" class="mt-1 text-sm text-danger">{{ form.errors.description }}</p>
            </label>

            <label class="mt-4 flex items-center gap-2 text-sm font-medium text-neutral-text">
                <input v-model="form.is_featured" type="checkbox" class="rounded border-neutral-border text-primary-hover focus:ring-primary" />
                Produk unggulan
            </label>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <AppButton type="button" variant="secondary" @click="close">Batal</AppButton>
                <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
            </div>
        </form>
    </Modal>
</template>
