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
    variant: { type: Object, default: null },
    statuses: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
    product_id: props.product.id,
    sku: '',
    variant_name: '',
    size: '',
    material: '',
    color: '',
    price: 0,
    stock: 0,
    status: 'aktif',
});

const title = computed(() => props.variant ? 'Edit Varian' : 'Tambah Varian');

function fillForm() {
    form.defaults({
        product_id: props.product.id,
        sku: props.variant?.sku || '',
        variant_name: props.variant?.variant_name || '',
        size: props.variant?.size || '',
        material: props.variant?.material || '',
        color: props.variant?.color || '',
        price: props.variant?.price || 0,
        stock: props.variant?.stock || 0,
        status: props.variant?.status || 'aktif',
    });
    form.reset();
    form.clearErrors();
}

watch(
    () => [props.show, props.variant, props.product.id],
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

    if (props.variant) {
        form.put(route('admin.variants.update', props.variant.id), options);
        return;
    }

    form.post(route('admin.variants.store'), options);
}
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-neutral-text">{{ title }}</h2>
                <p class="mt-1 text-sm text-neutral-muted">{{ product.name }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <FormInput id="product_variant_sku" v-model="form.sku" label="SKU" :error="form.errors.sku" />
                <FormInput id="product_variant_name" v-model="form.variant_name" label="Nama Varian" :error="form.errors.variant_name" />
                <FormSelect id="product_variant_status" v-model="form.status" label="Status" :options="statuses" :error="form.errors.status" required />
                <FormInput id="product_variant_size" v-model="form.size" label="Ukuran" :error="form.errors.size" />
                <FormInput id="product_variant_material" v-model="form.material" label="Bahan" :error="form.errors.material" />
                <FormInput id="product_variant_color" v-model="form.color" label="Warna" :error="form.errors.color" />
                <FormInput id="product_variant_price" v-model="form.price" type="number" label="Harga" :error="form.errors.price" required />
                <FormInput id="product_variant_stock" v-model="form.stock" type="number" label="Stok Fisik" :error="form.errors.stock" required />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <AppButton type="button" variant="secondary" @click="close">Batal</AppButton>
                <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
            </div>
        </form>
    </Modal>
</template>
