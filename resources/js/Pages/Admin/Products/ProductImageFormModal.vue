<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    product: { type: Object, required: true },
    variants: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
    product_id: props.product.id,
    product_variant_id: '',
    image: null,
    alt_text: '',
    sort_order: 0,
    is_primary: false,
});

const variantOptions = computed(() => [
    { value: '', label: 'Tanpa varian' },
    ...props.variants.map((variant) => ({
        value: variant.id,
        label: variant.variant_name || variant.sku || `Varian ${variant.id}`,
    })),
]);

function fillForm() {
    form.defaults({
        product_id: props.product.id,
        product_variant_id: '',
        image: null,
        alt_text: '',
        sort_order: 0,
        is_primary: props.product.images.length === 0,
    });
    form.reset();
    form.clearErrors();
}

watch(
    () => [props.show, props.product.id],
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
    form.product_id = props.product.id;
    form.post(route('admin.product-images.store'), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-neutral-text">Upload Gambar Produk</h2>
                <p class="mt-1 text-sm text-neutral-muted">{{ product.name }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <FormSelect id="product_image_variant_id" v-model="form.product_variant_id" label="Varian" :options="variantOptions" :error="form.errors.product_variant_id" />
                <FormInput id="product_image_sort_order" v-model="form.sort_order" type="number" label="Urutan" :error="form.errors.sort_order" required />
                <FormInput id="product_image_alt_text" v-model="form.alt_text" label="Alt Text" :error="form.errors.alt_text" />
            </div>

            <label class="mt-4 block" for="product_image_file">
                <span class="text-sm font-medium text-neutral-text">File Gambar<span class="text-danger"> *</span></span>
                <input id="product_image_file" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full rounded-md border border-neutral-border text-sm text-neutral-text file:mr-4 file:min-h-10 file:border-0 file:bg-neutral-light file:px-4 file:text-sm file:font-semibold" @input="form.image = $event.target.files[0]" />
                <p v-if="form.errors.image" class="mt-1 text-sm text-danger">{{ form.errors.image }}</p>
            </label>

            <label class="mt-4 flex items-center gap-2 text-sm font-medium text-neutral-text">
                <input v-model="form.is_primary" type="checkbox" class="rounded border-neutral-border text-primary-hover focus:ring-primary" />
                Jadikan gambar utama
            </label>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <AppButton type="button" variant="secondary" @click="close">Batal</AppButton>
                <AppButton type="submit" :loading="form.processing">Upload</AppButton>
            </div>
        </form>
    </Modal>
</template>
