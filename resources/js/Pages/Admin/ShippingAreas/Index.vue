<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import LeafletLocationPicker from '@/Components/UI/LeafletLocationPicker.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { useConfirm } from '@/Composables/useFeedback';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Edit, Plus, Save, Trash2 } from '@lucide/vue';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    areas: { type: Object, required: true },
});

const { confirm } = useConfirm();
const editingId = ref(null);
const formModalOpen = ref(false);

const areaForm = useForm({
    name: 'Toko Utama',
    description: '',
    latitude: '',
    longitude: '',
    radius_km: '',
    shipping_cost: '',
    is_active: true,
});

const columns = [
    { key: 'name', label: 'Titik asal' },
    { key: 'radius_km', label: 'Radius' },
    { key: 'shipping_cost', label: 'Tarif per KM' },
    { key: 'is_active', label: 'Status' },
];

const modalTitle = computed(() => editingId.value ? 'Edit Aturan Ongkir' : 'Tambah Aturan Ongkir');

function fillForm(area = null) {
    editingId.value = area?.id ?? null;
    areaForm.name = area?.name || 'Toko Utama';
    areaForm.description = area?.description || '';
    areaForm.latitude = area?.latitude ?? '';
    areaForm.longitude = area?.longitude ?? '';
    areaForm.radius_km = area?.radius_km ?? '';
    areaForm.shipping_cost = area?.shipping_cost_per_km ?? area?.shipping_cost ?? '';
    areaForm.is_active = area?.is_active ?? true;
    areaForm.clearErrors();
}

function openCreateModal() {
    fillForm();
    formModalOpen.value = true;
}

function edit(area) {
    fillForm(area);
    formModalOpen.value = true;
}

function closeModal() {
    if (areaForm.processing) {
        return;
    }

    formModalOpen.value = false;
    fillForm();
}

function submitArea() {
    const options = {
        preserveScroll: true,
        onSuccess: closeModal,
    };

    if (editingId.value) {
        areaForm.put(route('admin.shipping-areas.update', editingId.value), options);
        return;
    }

    areaForm.post(route('admin.shipping-areas.store'), options);
}

async function destroyArea(area) {
    if (await confirm({
        title: 'Hapus aturan ongkir?',
        message: `Aturan ongkir ${area.name} akan dihapus.`,
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
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-neutral-text">Pengaturan ongkir radius</h2>
            <AppButton type="button" @click="openCreateModal">
                <Plus class="h-4 w-4" />
                Tambah Aturan
            </AppButton>
        </div>

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
                <EmptyState title="Belum ada aturan ongkir">
                    <template #actions>
                        <AppButton type="button" @click="openCreateModal">Tambah Aturan</AppButton>
                    </template>
                </EmptyState>
            </template>
        </DataTable>

        <Pagination class="mt-4" :links="areas.links" />

        <Modal :show="formModalOpen" max-width="5xl" :closeable="!areaForm.processing" @close="closeModal">
            <form class="p-6" @submit.prevent="submitArea">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-neutral-text">{{ modalTitle }}</h2>
                </div>

                <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,420px)_1fr]">
                    <div class="grid self-start gap-4">
                        <FormInput id="name" v-model="areaForm.name" label="Nama titik asal" :error="areaForm.errors.name" required />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormInput id="radius_km" v-model="areaForm.radius_km" type="number" label="Radius layanan maksimal (km)" :error="areaForm.errors.radius_km" required />
                            <FormInput id="shipping_cost" v-model="areaForm.shipping_cost" type="number" label="Tarif ongkir per KM (Rp)" :error="areaForm.errors.shipping_cost" required />
                        </div>
                        <label class="block" for="description">
                            <span class="text-sm font-medium text-neutral-text">Deskripsi dan catatan operasional</span>
                            <textarea id="description" v-model="areaForm.description" rows="3" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                            <p v-if="areaForm.errors.description" class="mt-1 text-sm text-danger">{{ areaForm.errors.description }}</p>
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium text-neutral-text">
                            <input v-model="areaForm.is_active" type="checkbox" class="rounded border-neutral-border text-primary-hover focus:ring-primary" />
                            Aktif digunakan checkout
                        </label>
                        <p v-if="areaForm.errors.is_active" class="text-sm text-danger">{{ areaForm.errors.is_active }}</p>
                    </div>

                    <LeafletLocationPicker
                        v-model:latitude="areaForm.latitude"
                        v-model:longitude="areaForm.longitude"
                        class="self-start"
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

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <AppButton type="button" variant="secondary" @click="closeModal">Batal</AppButton>
                    <AppButton type="submit" :loading="areaForm.processing">
                        <Save class="h-4 w-4" />
                        Simpan Aturan Ongkir
                    </AppButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
