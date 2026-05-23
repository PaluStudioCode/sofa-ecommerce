<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import LeafletLocationPicker from '@/Components/UI/LeafletLocationPicker.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { useConfirm } from '@/Composables/useFeedback';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Edit, Save, Trash2 } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    areas: { type: Object, required: true },
    currentRule: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    activeOptions: { type: Array, default: () => [] },
});

const { confirm } = useConfirm();
const editingId = ref(props.currentRule?.id ?? null);

const filterForm = useForm({
    keyword: props.filters.keyword || '',
    is_active: props.filters.is_active || '',
});

const areaForm = useForm({
    name: props.currentRule?.name || 'Toko Utama',
    description: props.currentRule?.description || '',
    latitude: props.currentRule?.latitude ?? '',
    longitude: props.currentRule?.longitude ?? '',
    radius_km: props.currentRule?.radius_km ?? '',
    shipping_cost: props.currentRule?.shipping_cost_per_km ?? props.currentRule?.shipping_cost ?? '',
    is_active: props.currentRule?.is_active ?? true,
});

const columns = [
    { key: 'name', label: 'Titik asal' },
    { key: 'radius_km', label: 'Radius' },
    { key: 'shipping_cost', label: 'Tarif per KM' },
    { key: 'orders_count', label: 'Order' },
    { key: 'is_active', label: 'Status' },
];

function submitFilters() {
    filterForm.get(route('admin.shipping-areas.index'), { preserveState: true, replace: true });
}

function edit(area) {
    editingId.value = area.id;
    areaForm.name = area.name;
    areaForm.description = area.description || '';
    areaForm.latitude = area.latitude;
    areaForm.longitude = area.longitude;
    areaForm.radius_km = area.radius_km;
    areaForm.shipping_cost = area.shipping_cost_per_km ?? area.shipping_cost;
    areaForm.is_active = area.is_active;
    areaForm.clearErrors();
}

function reset() {
    if (props.currentRule) {
        edit(props.currentRule);
    } else {
        editingId.value = null;
        areaForm.reset();
        areaForm.name = 'Toko Utama';
        areaForm.is_active = true;
    }

    areaForm.clearErrors();
}

function submitArea() {
    if (editingId.value) {
        areaForm.put(route('admin.shipping-areas.update', editingId.value), { onSuccess: reset });
        return;
    }

    areaForm.post(route('admin.shipping-areas.store'), { onSuccess: reset });
}

async function destroyArea(area) {
    if (await confirm({
        title: 'Hapus aturan ongkir?',
        message: `Aturan ongkir ${area.name} akan dihapus atau dinonaktifkan jika sudah dipakai.`,
        confirmText: 'Hapus',
        tone: 'danger',
    })) {
        router.delete(route('admin.shipping-areas.destroy', area.id));
    }
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}
</script>

<template>
    <Head title="Aturan Ongkir Radius" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Aturan Ongkir Radius">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-neutral-text">Pengaturan ongkir radius</h2>
            <p class="mt-1 text-sm text-neutral-muted">Satu aturan aktif dipakai checkout: jarak customer dari titik asal dikalikan tarif per KM.</p>
        </div>

        <div class="mb-5 grid gap-4 xl:grid-cols-[minmax(0,1fr)_460px]">
            <form class="rounded-md border border-neutral-border bg-white p-5" @submit.prevent="submitArea">
                <div class="grid gap-4 md:grid-cols-2">
                    <FormInput id="name" v-model="areaForm.name" label="Nama titik asal" :error="areaForm.errors.name" required />
                    <FormInput id="radius_km" v-model="areaForm.radius_km" type="number" label="Radius layanan maksimal (km)" :error="areaForm.errors.radius_km" required />
                    <FormInput id="shipping_cost" v-model="areaForm.shipping_cost" type="number" label="Tarif ongkir per KM (Rp)" :error="areaForm.errors.shipping_cost" required />
                    <label class="flex items-center gap-2 pt-7 text-sm font-medium text-neutral-text">
                        <input v-model="areaForm.is_active" type="checkbox" class="rounded border-neutral-border text-primary-hover focus:ring-primary" />
                        Aktif digunakan checkout
                    </label>
                    <label class="block md:col-span-2" for="description">
                        <span class="text-sm font-medium text-neutral-text">Deskripsi dan catatan operasional</span>
                        <textarea id="description" v-model="areaForm.description" rows="3" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                        <p v-if="areaForm.errors.description" class="mt-1 text-sm text-danger">{{ areaForm.errors.description }}</p>
                    </label>
                </div>
                <p v-if="areaForm.errors.is_active" class="mt-2 text-sm text-danger">{{ areaForm.errors.is_active }}</p>
                <div class="mt-4 flex gap-2">
                    <AppButton type="submit" :loading="areaForm.processing">
                        <Save class="h-4 w-4" />
                        Simpan Aturan Ongkir
                    </AppButton>
                    <AppButton v-if="editingId" type="button" variant="secondary" @click="reset">Batal</AppButton>
                </div>
            </form>

            <LeafletLocationPicker
                v-model:latitude="areaForm.latitude"
                v-model:longitude="areaForm.longitude"
                title="Titik asal pengiriman dan radius"
                marker-label="Titik asal belum dipilih"
                helper="Klik peta untuk menentukan titik asal pengiriman."
                search-placeholder="Cari alamat titik asal"
                :radius-km="areaForm.radius_km"
                :show-radius="true"
                :error="areaForm.errors.latitude || areaForm.errors.longitude"
            >
                <template #actions>
                    <span class="text-xs text-neutral-muted">{{ Number(areaForm.radius_km || 0) }} km</span>
                </template>
            </LeafletLocationPicker>
        </div>

        <form class="mb-4 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[1fr_220px_auto]" @submit.prevent="submitFilters">
            <FormInput id="keyword" v-model="filterForm.keyword" label="Keyword" placeholder="Cari titik asal atau catatan" />
            <FormSelect id="is_active" v-model="filterForm.is_active" label="Status" :options="activeOptions" />
            <div class="flex items-end">
                <AppButton type="submit">Filter</AppButton>
            </div>
        </form>

        <DataTable :columns="columns" :rows="areas.data">
            <template #cell-name="{ row }">
                <div>
                    <p class="font-semibold text-neutral-text">{{ row.name }}</p>
                    <p class="text-xs text-neutral-muted">{{ row.center_summary }}</p>
                    <p v-if="row.description" class="max-w-md truncate text-xs text-neutral-muted">{{ row.description }}</p>
                </div>
            </template>
            <template #cell-radius_km="{ value }">{{ value }} km</template>
            <template #cell-shipping_cost="{ value }">{{ formatRupiah(value) }}/km</template>
            <template #cell-is_active="{ value }"><StatusBadge :status="value ? 'aktif' : 'nonaktif'" /></template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="edit(row)">
                        <Edit class="h-4 w-4" />
                        <span class="sr-only">Edit</span>
                    </button>
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="destroyArea(row)">
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Hapus</span>
                    </button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="Belum ada aturan ongkir" />
            </template>
        </DataTable>

        <Pagination class="mt-4" :links="areas.links" />
    </AuthenticatedLayout>
</template>
