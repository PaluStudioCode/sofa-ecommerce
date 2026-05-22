<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    voucher: { type: Object, default: null },
    statusOptions: { type: Array, default: () => [] },
    discountTypeOptions: { type: Array, default: () => [] },
});

const title = computed(() => props.voucher ? 'Edit Voucher' : 'Tambah Voucher');
const form = useForm({
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

function submit() {
    form.transform((data) => ({
        ...data,
        code: String(data.code).toUpperCase(),
        max_discount: data.max_discount === '' ? null : data.max_discount,
        quota: data.quota === '' ? null : data.quota,
        per_user_limit: data.per_user_limit === '' ? null : data.per_user_limit,
    }));

    if (props.voucher) {
        form.put(route('admin.vouchers.update', props.voucher.id));
        return;
    }

    form.post(route('admin.vouchers.store'));
}
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="title">
        <form class="max-w-5xl rounded-md border border-neutral-border bg-white p-5 shadow-sm" @submit.prevent="submit">
            <div v-if="voucher" class="mb-5 flex flex-wrap items-center gap-3 rounded-md bg-neutral-light p-4">
                <StatusBadge :status="voucher.status" />
                <p class="text-sm text-neutral-muted">Digunakan {{ voucher.used_count }} kali. Nilai ini dikelola otomatis saat checkout.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <FormInput id="code" v-model="form.code" label="Kode Voucher" :error="form.errors.code" required />
                <FormInput id="name" v-model="form.name" label="Nama Voucher" :error="form.errors.name" required />
                <FormSelect id="discount_type" v-model="form.discount_type" label="Tipe Diskon" :options="discountTypeOptions" :error="form.errors.discount_type" required />
                <FormInput id="discount_value" v-model="form.discount_value" type="number" label="Nilai Diskon" :error="form.errors.discount_value" required />
                <FormInput id="max_discount" v-model="form.max_discount" type="number" label="Maks Diskon" :error="form.errors.max_discount" />
                <FormInput id="minimum_purchase" v-model="form.minimum_purchase" type="number" label="Minimum Pembelian" :error="form.errors.minimum_purchase" required />
                <FormInput id="quota" v-model="form.quota" type="number" label="Kuota" :error="form.errors.quota" />
                <FormInput id="per_user_limit" v-model="form.per_user_limit" type="number" label="Limit per User" :error="form.errors.per_user_limit" />
                <FormInput id="start_at" v-model="form.start_at" type="datetime-local" label="Mulai" :error="form.errors.start_at" required />
                <FormInput id="end_at" v-model="form.end_at" type="datetime-local" label="Selesai" :error="form.errors.end_at" required />
                <FormSelect id="status" v-model="form.status" label="Status" :options="statusOptions" :error="form.errors.status" required />
            </div>

            <label class="mt-4 block" for="description">
                <span class="text-sm font-medium text-neutral-text">Deskripsi</span>
                <textarea id="description" v-model="form.description" rows="4" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                <p v-if="form.errors.description" class="mt-1 text-sm text-danger">{{ form.errors.description }}</p>
            </label>

            <div class="mt-6 flex flex-wrap gap-2">
                <AppButton type="submit" :loading="form.processing">Simpan</AppButton>
                <AppButton :href="route('admin.vouchers.index')" variant="secondary">Batal</AppButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
