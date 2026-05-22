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
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    areas: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    activeOptions: { type: Array, default: () => [] },
});

const page = usePage();
const editingId = ref(null);

const filterForm = useForm({
    keyword: props.filters.keyword || '',
    is_active: props.filters.is_active || '',
});

const areaForm = useForm({
    name: '',
    description: '',
    latitude: '',
    longitude: '',
    radius_km: '',
    shipping_cost: '',
    priority: 0,
    is_active: true,
});

const columns = [
    { key: 'name', label: 'Toko' },
    { key: 'radius_km', label: 'Radius' },
    { key: 'shipping_cost', label: 'Ongkir' },
    { key: 'priority', label: 'Priority' },
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
    areaForm.shipping_cost = area.shipping_cost;
    areaForm.priority = area.priority;
    areaForm.is_active = area.is_active;
    areaForm.clearErrors();
}

function reset() {
    editingId.value = null;
    areaForm.reset();
    areaForm.clearErrors();
}

function submitArea() {
    if (editingId.value) {
        areaForm.put(route('admin.shipping-areas.update', editingId.value), { onSuccess: reset });
        return;
    }

    areaForm.post(route('admin.shipping-areas.store'), { onSuccess: reset });
}

function destroyArea(area) {
    if (window.confirm(`Hapus atau nonaktifkan toko ${area.name}?`)) {
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
    <Head title="Toko & Radius Layanan" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Toko & Radius Layanan">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-neutral-text">Manajemen toko dan radius layanan</h2>
            <p v-if="page.props.flash?.success" class="mt-1 text-sm text-success">{{ page.props.flash.success }}</p>
            <p v-if="page.props.flash?.error" class="mt-1 text-sm text-danger">{{ page.props.flash.error }}</p>
        </div>

        <div class="mb-5 grid gap-4 xl:grid-cols-[minmax(0,1fr)_460px]">
            <form class="rounded-md border border-neutral-border bg-white p-5" @submit.prevent="submitArea">
                <div class="grid gap-4 md:grid-cols-2">
                    <FormInput id="name" v-model="areaForm.name" label="Nama toko" :error="areaForm.errors.name" required />
                    <FormInput id="radius_km" v-model="areaForm.radius_km" type="number" label="Radius (km)" :error="areaForm.errors.radius_km" required />
                    <FormInput id="shipping_cost" v-model="areaForm.shipping_cost" type="number" label="Biaya ongkir" :error="areaForm.errors.shipping_cost" required />
                    <FormInput id="priority" v-model="areaForm.priority" type="number" label="Priority" :error="areaForm.errors.priority" required />
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
                        <Plus class="h-4 w-4" />
                        {{ editingId ? 'Update Toko' : 'Tambah Toko' }}
                    </AppButton>
                    <AppButton v-if="editingId" type="button" variant="secondary" @click="reset">Batal</AppButton>
                </div>
            </form>

            <LeafletLocationPicker
                v-model:latitude="areaForm.latitude"
                v-model:longitude="areaForm.longitude"
                title="Titik toko dan radius"
                marker-label="Titik toko belum dipilih"
                helper="Klik peta untuk menentukan titik toko."
                search-placeholder="Cari alamat toko"
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
            <FormInput id="keyword" v-model="filterForm.keyword" label="Keyword" placeholder="Cari toko atau catatan" />
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
            <template #cell-shipping_cost="{ value }">{{ formatRupiah(value) }}</template>
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
                <EmptyState title="Belum ada toko layanan" />
            </template>
        </DataTable>

        <Pagination class="mt-4" :links="areas.links" />
    </AuthenticatedLayout>
</template>
