<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, MapPin, Save } from '@lucide/vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import LeafletLocationPicker from '@/Components/UI/LeafletLocationPicker.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';

const props = defineProps({
    address: { type: Object, default: null },
});

const form = useForm({
    latitude: props.address?.latitude ?? '',
    longitude: props.address?.longitude ?? '',
    formatted_address: props.address?.formatted_address || '',
    city: props.address?.city || '',
    district: props.address?.district || '',
    postal_code: props.address?.postal_code || '',
});

const hasCoordinates = computed(() => form.latitude !== null
    && form.latitude !== undefined
    && form.latitude !== ''
    && form.longitude !== null
    && form.longitude !== undefined
    && form.longitude !== '');
const hasCompleteAddress = computed(() => hasCoordinates.value && Boolean(form.formatted_address));
const addressMeta = computed(() => [form.district, form.city, form.postal_code].filter(Boolean).join(', '));
const statusLabel = computed(() => {
    if (form.processing) {
        return 'Menyimpan';
    }

    if (hasCompleteAddress.value) {
        return 'Siap disimpan';
    }

    return hasCoordinates.value ? 'Mengambil alamat' : 'Belum dipilih';
});

function syncAddressDetails(details) {
    form.formatted_address = details.formatted_address || form.formatted_address;
    form.city = details.city || '';
    form.district = details.district || '';
    form.postal_code = details.postal_code || '';
}

function saveAddress() {
    form.post(route('address.update'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Alamat Pengiriman" />

    <PublicLayout>
        <section class="bg-neutral-light py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <Link href="/checkout" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-muted hover:text-neutral-text">
                            <ArrowLeft class="h-4 w-4" />
                            Checkout
                        </Link>
                        <h1 class="mt-2 text-3xl font-bold text-neutral-text">Alamat Pengiriman</h1>
                        <p class="mt-1 text-sm text-neutral-muted">Alamat ini dipakai checkout untuk menghitung ongkir dan membuat pesanan.</p>
                    </div>
                    <AppButton href="/checkout" variant="secondary">Ke Checkout</AppButton>
                </div>

                <form class="grid gap-5 lg:grid-cols-[1fr_360px]" @submit.prevent="saveAddress">
                    <LeafletLocationPicker
                        v-model:latitude="form.latitude"
                        v-model:longitude="form.longitude"
                        v-model:address="form.formatted_address"
                        title="Titik Alamat"
                        marker-label="Alamat belum dipilih"
                        pending-address-label="Mengambil alamat lengkap..."
                        helper="Cari alamat, klik peta, atau seret pin untuk menentukan titik pengiriman."
                        search-placeholder="Cari alamat pengiriman"
                        :error="form.errors.latitude || form.errors.longitude || form.errors.formatted_address"
                        @address-details="syncAddressDetails"
                    >
                        <template #actions>
                            <StatusBadge :status="hasCompleteAddress ? 'aktif' : 'pending'" :label="statusLabel" />
                        </template>
                    </LeafletLocationPicker>

                    <aside class="h-fit rounded-md border border-neutral-border bg-white p-5">
                        <div class="flex items-start gap-3">
                            <MapPin class="mt-0.5 h-5 w-5 shrink-0 text-info" />
                            <div>
                                <h2 class="text-lg font-semibold text-neutral-text">Detail alamat</h2>
                                <p class="mt-1 text-sm text-neutral-muted">{{ hasCompleteAddress ? 'Alamat lengkap sudah terbaca dari titik peta.' : 'Pilih titik pengiriman di peta.' }}</p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-md bg-neutral-light p-4">
                            <p class="text-sm font-semibold text-neutral-text">{{ form.formatted_address || 'Belum ada alamat tersimpan' }}</p>
                            <p v-if="addressMeta" class="mt-2 text-sm text-neutral-muted">{{ addressMeta }}</p>
                            <p v-if="hasCoordinates" class="mt-2 text-xs text-neutral-muted">{{ Number(form.latitude).toFixed(6) }}, {{ Number(form.longitude).toFixed(6) }}</p>
                        </div>

                        <Alert v-if="form.errors.city || form.errors.district || form.errors.postal_code" tone="danger" class="mt-4">
                            {{ form.errors.city || form.errors.district || form.errors.postal_code }}
                        </Alert>

                        <div class="mt-5 grid gap-2">
                            <AppButton type="submit" class="w-full" :disabled="!hasCompleteAddress" :loading="form.processing">
                                <Save class="h-4 w-4" />
                                Simpan Alamat
                            </AppButton>
                            <AppButton href="/checkout" variant="secondary" class="w-full">Kembali ke Checkout</AppButton>
                        </div>
                    </aside>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>
