<script setup>
import { watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import LeafletLocationPicker from '@/Components/UI/LeafletLocationPicker.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    setting: { type: Object, default: null },
});

function formDefaults(setting = null) {
    return {
        name: setting?.name || 'Toko Utama',
        description: setting?.description || '',
        latitude: setting?.latitude ?? '',
        longitude: setting?.longitude ?? '',
        radius_km: setting?.radius_km ?? '',
        shipping_cost: setting?.shipping_cost_per_km ?? setting?.shipping_cost ?? '',
        is_active: true,
    };
}

const form = useForm(formDefaults(props.setting));

watch(
    () => props.setting,
    (setting) => {
        form.defaults(formDefaults(setting));
        form.reset();
        form.clearErrors();
    },
);

function submitSetting() {
    const options = {
        preserveScroll: true,
        onSuccess: () => form.clearErrors(),
    };

    if (props.setting?.id) {
        form.put(route('admin.shipping-areas.update', props.setting.id), options);
        return;
    }

    form.post(route('admin.shipping-areas.store'), options);
}
</script>

<template>
    <Head title="Pengaturan Ongkir" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Pengaturan Ongkir">
        <div class="mb-5">
            <h2 class="text-xl font-semibold text-neutral-text">Pengaturan ongkir toko</h2>
        </div>

        <form class="grid items-start gap-5 lg:grid-cols-[minmax(0,420px)_1fr]" @submit.prevent="submitSetting">
            <section class="grid gap-4 rounded-md border border-neutral-border bg-white p-5">
                <FormInput id="shipping_name" v-model="form.name" label="Nama titik asal" :error="form.errors.name" required />

                <div class="grid gap-4 sm:grid-cols-2">
                    <FormInput id="shipping_radius_km" v-model="form.radius_km" type="number" label="Radius layanan maksimal (km)" :error="form.errors.radius_km" required />
                    <FormInput id="shipping_cost" v-model="form.shipping_cost" type="number" label="Tarif ongkir per KM (Rp)" :error="form.errors.shipping_cost" required />
                </div>

                <label class="block" for="shipping_description">
                    <span class="text-sm font-medium text-neutral-text">Alamat/catatan titik asal</span>
                    <textarea
                        id="shipping_description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-sm text-danger">{{ form.errors.description }}</p>
                </label>

                <p v-if="form.errors.is_active" class="text-sm text-danger">{{ form.errors.is_active }}</p>

                <div class="pt-2">
                    <AppButton type="submit" :loading="form.processing">
                        <Save class="h-4 w-4" />
                        Simpan Pengaturan
                    </AppButton>
                </div>
            </section>

            <LeafletLocationPicker
                v-model:latitude="form.latitude"
                v-model:longitude="form.longitude"
                class="self-start"
                title="Titik asal pengiriman dan radius"
                marker-label="Titik asal belum dipilih"
                helper="Cari alamat, klik peta, atau gunakan GPS untuk menentukan titik asal pengiriman."
                search-placeholder="Cari alamat titik asal"
                current-location-label="Gunakan GPS"
                :radius-km="form.radius_km"
                :show-radius="true"
                :error="form.errors.latitude || form.errors.longitude"
            >
                <template #actions>
                    <span class="text-xs text-neutral-muted">{{ Number(form.radius_km || 0) }} km</span>
                </template>
            </LeafletLocationPicker>
        </form>
    </AuthenticatedLayout>
</template>
