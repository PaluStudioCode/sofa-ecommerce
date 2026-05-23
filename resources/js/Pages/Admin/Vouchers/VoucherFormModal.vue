<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    voucher: { type: Object, default: null },
    statusOptions: { type: Array, default: () => [] },
    discountTypeOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
    code: '',
    name: '',
    description: '',
    discount_type: 'nominal',
    discount_value: 0,
    max_discount: '',
    minimum_purchase: 0,
    quota: '',
    per_user_limit: '',
    start_at: '',
    end_at: '',
    status: 'aktif',
});

const title = computed(() => props.voucher ? 'Edit Voucher' : 'Tambah Voucher');

function fillForm() {
    form.defaults({
        code: props.voucher?.code || '',
        name: props.voucher?.name || '',
        description: props.voucher?.description || '',
        discount_type: props.voucher?.discount_type || 'nominal',
        discount_value: props.voucher?.discount_value || 0,
        max_discount: props.voucher?.max_discount ?? '',
        minimum_purchase: props.voucher?.minimum_purchase || 0,
        quota: props.voucher?.quota ?? '',
        per_user_limit: props.voucher?.per_user_limit ?? '',
        start_at: props.voucher?.start_at || '',
        end_at: props.voucher?.end_at || '',
        status: props.voucher?.status || 'aktif',
    });
    form.reset();
    form.clearErrors();
}

watch(
    () => [props.show, props.voucher],
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
    form.transform((data) => ({
        ...data,
        code: String(data.code).toUpperCase(),
        max_discount: data.max_discount === '' ? null : data.max_discount,
        quota: data.quota === '' ? null : data.quota,
        per_user_limit: data.per_user_limit === '' ? null : data.per_user_limit,
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    };

    if (props.voucher) {
        form.put(route('admin.vouchers.update', props.voucher.id), options);
        return;
    }

    form.post(route('admin.vouchers.store'), options);
}
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-neutral-text">{{ title }}</h2>
                <div v-if="voucher" class="mt-3 flex flex-wrap items-center gap-3 rounded-md bg-neutral-light p-3">
                    <StatusBadge :status="voucher.status" />
                    <p class="text-sm text-neutral-muted">Digunakan {{ voucher.used_count }} kali.</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <FormInput id="voucher_code" v-model="form.code" label="Kode Voucher" :error="form.errors.code" required />
                <FormInput id="voucher_name" v-model="form.name" label="Nama Voucher" :error="form.errors.name" required />
                <FormSelect id="voucher_discount_type" v-model="form.discount_type" label="Tipe Diskon" :options="discountTypeOptions" :error="form.errors.discount_type" required />
                <FormInput id="voucher_discount_value" v-model="form.discount_value" type="number" label="Nilai Diskon" :error="form.errors.discount_value" required />
                <FormInput id="voucher_max_discount" v-model="form.max_discount" type="number" label="Maks Diskon" :error="form.errors.max_discount" />
                <FormInput id="voucher_minimum_purchase" v-model="form.minimum_purchase" type="number" label="Minimum Pembelian" :error="form.errors.minimum_purchase" required />
                <FormInput id="voucher_quota" v-model="form.quota" type="number" label="Kuota" :error="form.errors.quota" />
                <FormInput id="voucher_per_user_limit" v-model="form.per_user_limit" type="number" label="Limit per User" :error="form.errors.per_user_limit" />
                <FormInput id="voucher_start_at" v-model="form.start_at" type="datetime-local" label="Mulai" :error="form.errors.start_at" required />
                <FormInput id="voucher_end_at" v-model="form.end_at" type="datetime-local" label="Selesai" :error="form.errors.end_at" required />
                <FormSelect id="voucher_status" v-model="form.status" label="Status" :options="statusOptions" :error="form.errors.status" required />
            </div>

            <label class="mt-4 block" for="voucher_description">
                <span class="text-sm font-medium text-neutral-text">Deskripsi</span>
                <textarea id="voucher_description" v-model="form.description" rows="4" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                <p v-if="form.errors.description" class="mt-1 text-sm text-danger">{{ form.errors.description }}</p>
            </label>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <AppButton type="button" variant="secondary" @click="close">Batal</AppButton>
                <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
            </div>
        </form>
    </Modal>
</template>
