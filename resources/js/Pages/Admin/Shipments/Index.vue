<script setup>
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Edit, Truck } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    formStatusOptions: { type: Array, default: () => [] },
});

const selectedOrder = ref(null);
const shipmentModalOpen = ref(false);
let closeResetTimer = null;
const statusLabels = {
    belum_dijadwalkan: 'Belum dijadwalkan',
    dijadwalkan: 'Dijadwalkan',
    dalam_pengiriman: 'Dalam pengiriman',
    terkirim: 'Terkirim',
    gagal_dikirim: 'Gagal dikirim',
};
const nextStatuses = {
    belum_dijadwalkan: ['dijadwalkan'],
    dijadwalkan: ['dalam_pengiriman', 'gagal_dikirim'],
    dalam_pengiriman: ['terkirim', 'gagal_dikirim'],
    gagal_dikirim: ['dijadwalkan'],
    terkirim: [],
};

const filterForm = useForm({
    keyword: props.filters.keyword || '',
    status: props.filters.status || '',
});

const shipmentForm = useForm({
    status: 'belum_dijadwalkan',
    scheduled_at: '',
    delivered_at: '',
    driver_name: '',
    driver_phone: '',
    vehicle_note: '',
    shipping_note: '',
});

const columns = [
    { key: 'order_number', label: 'Order' },
    { key: 'customer_name', label: 'Customer' },
    { key: 'total_amount', label: 'Total' },
    { key: 'order_status', label: 'Order' },
    { key: 'shipment_status', label: 'Shipment' },
    { key: 'scheduled_at', label: 'Jadwal' },
];

const currentShipmentStatus = computed(() => selectedOrder.value?.shipment?.status || selectedOrder.value?.shipment_status || 'belum_dijadwalkan');
const allowedStatuses = computed(() => {
    if (!selectedOrder.value) {
        return [];
    }

    return selectedOrder.value.allowed_statuses || [currentShipmentStatus.value, ...(nextStatuses[currentShipmentStatus.value] || [])];
});
const availableStatusOptions = computed(() => props.formStatusOptions.filter((option) => allowedStatuses.value.includes(option.value)));
const actionStatusOptions = computed(() => availableStatusOptions.value.filter((option) => option.value !== currentShipmentStatus.value));

function submitFilters() {
    filterForm.get(route('admin.shipments.index'), { preserveState: true, replace: true });
}

function selectOrder(order) {
    if (closeResetTimer) {
        window.clearTimeout(closeResetTimer);
        closeResetTimer = null;
    }

    selectedOrder.value = order;
    fillForm(order);
    shipmentModalOpen.value = true;
}

function fillForm(order) {
    shipmentForm.status = order.shipment?.status || order.shipment_status || 'belum_dijadwalkan';
    shipmentForm.scheduled_at = order.shipment?.scheduled_at || '';
    shipmentForm.delivered_at = order.shipment?.delivered_at || '';
    shipmentForm.driver_name = order.shipment?.driver_name || '';
    shipmentForm.driver_phone = order.shipment?.driver_phone || '';
    shipmentForm.vehicle_note = order.shipment?.vehicle_note || '';
    shipmentForm.shipping_note = order.shipment?.shipping_note || '';
    shipmentForm.clearErrors();
}

function nowInputValue() {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());

    return date.toISOString().slice(0, 16);
}

function orderStatusForShipment(status) {
    return {
        belum_dijadwalkan: 'diproses',
        dijadwalkan: 'diproses',
        gagal_dikirim: 'diproses',
        dalam_pengiriman: 'dikirim',
        terkirim: 'selesai',
    }[status] || selectedOrder.value?.order_status;
}

function chooseStatus(status) {
    shipmentForm.status = status;

    if (['dijadwalkan', 'dalam_pengiriman', 'terkirim'].includes(status) && !shipmentForm.scheduled_at) {
        shipmentForm.scheduled_at = nowInputValue();
    }

    if (status === 'terkirim' && !shipmentForm.delivered_at) {
        shipmentForm.delivered_at = nowInputValue();
    }

    if (status !== 'terkirim') {
        shipmentForm.delivered_at = '';
    }
}

function applyLocalShipmentState() {
    if (!selectedOrder.value) {
        return;
    }

    const status = shipmentForm.status;

    selectedOrder.value = {
        ...selectedOrder.value,
        order_status: orderStatusForShipment(status),
        shipment_status: status,
        allowed_statuses: [status, ...(nextStatuses[status] || [])],
        shipment: {
            ...(selectedOrder.value.shipment || {}),
            status,
            scheduled_at: shipmentForm.scheduled_at,
            delivered_at: shipmentForm.delivered_at,
            driver_name: shipmentForm.driver_name,
            driver_phone: shipmentForm.driver_phone,
            vehicle_note: shipmentForm.vehicle_note,
            shipping_note: shipmentForm.shipping_note,
        },
    };
}

function submitShipment() {
    if (!selectedOrder.value) return;

    shipmentForm.put(route('admin.shipments.update', selectedOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            applyLocalShipmentState();
            shipmentForm.clearErrors();
            closeShipmentModal(true);
        },
    });
}

function closeShipmentModal(force = false) {
    if (shipmentForm.processing && !force) {
        return;
    }

    shipmentModalOpen.value = false;
    if (closeResetTimer) {
        window.clearTimeout(closeResetTimer);
    }

    closeResetTimer = window.setTimeout(() => {
        selectedOrder.value = null;
        shipmentForm.reset();
        shipmentForm.clearErrors();
        closeResetTimer = null;
    }, 200);
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}

function formatDate(value) {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function shipmentStatusLabel(status) {
    return statusLabels[status] || status;
}

watch(() => shipmentForm.status, (status) => {
    if (!selectedOrder.value) {
        return;
    }

    if (['dijadwalkan', 'dalam_pengiriman', 'terkirim'].includes(status) && !shipmentForm.scheduled_at) {
        shipmentForm.scheduled_at = nowInputValue();
    }

    if (status === 'terkirim' && !shipmentForm.delivered_at) {
        shipmentForm.delivered_at = nowInputValue();
    }

    if (status !== 'terkirim' && shipmentForm.delivered_at) {
        shipmentForm.delivered_at = '';
    }
});
</script>

<template>
    <Head title="Pengiriman Internal" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Pengiriman Internal">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-neutral-text">Manajemen pengiriman internal</h2>
            <p class="mt-1 text-sm text-neutral-muted">Kelola jadwal, petugas, kendaraan, dan status kirim untuk order yang sudah dibayar.</p>
        </div>

        <div class="mb-5">
            <form class="mb-4 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[1fr_220px_auto]" @submit.prevent="submitFilters">
                <FormInput id="keyword" v-model="filterForm.keyword" label="Keyword" placeholder="Nomor order, customer, email" />
                <FormSelect id="status" v-model="filterForm.status" label="Status shipment" :options="statusOptions" />
                <div class="flex items-end">
                    <AppButton type="submit">Filter</AppButton>
                </div>
            </form>

            <DataTable :columns="columns" :rows="orders.data">
                <template #cell-order_number="{ row }">
                    <div>
                        <p class="font-semibold text-neutral-text">{{ row.order_number }}</p>
                        <p class="text-xs text-neutral-muted">{{ row.customer_phone }}</p>
                    </div>
                </template>
                <template #cell-customer_name="{ row }">
                    <div>
                        <p class="font-semibold text-neutral-text">{{ row.customer_name }}</p>
                        <p class="text-xs text-neutral-muted">{{ row.customer_email }}</p>
                    </div>
                </template>
                <template #cell-total_amount="{ value }">{{ formatRupiah(value) }}</template>
                <template #cell-order_status="{ value }"><StatusBadge :status="value" /></template>
                <template #cell-shipment_status="{ value }"><StatusBadge :status="value" /></template>
                <template #cell-scheduled_at="{ row }">{{ formatDate(row.shipment?.scheduled_at) }}</template>
                <template #actions="{ row }">
                    <button type="button" class="inline-flex h-9 items-center gap-2 rounded-md border border-neutral-border px-3 text-sm hover:bg-neutral-light" @click="selectOrder(row)">
                        <Edit class="h-4 w-4" />
                        Kelola
                    </button>
                </template>
                <template #empty>
                    <EmptyState title="Belum ada order siap kirim" />
                </template>
            </DataTable>

            <Pagination class="mt-4" :links="orders.links" />
        </div>

        <Modal :show="shipmentModalOpen" max-width="2xl" :closeable="!shipmentForm.processing" @close="closeShipmentModal">
            <form class="p-5" @submit.prevent="submitShipment">
                <div class="mb-4 flex items-start gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-md bg-primary-soft text-neutral-text">
                        <Truck class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-neutral-text">{{ selectedOrder ? selectedOrder.order_number : 'Pilih order' }}</h3>
                        <p class="text-sm text-neutral-muted">{{ selectedOrder ? selectedOrder.shipping_address : 'Shipment dibuat atau diperbarui dari order berbayar.' }}</p>
                    </div>
                </div>

                <div v-if="selectedOrder" class="mb-4 rounded-md bg-neutral-light p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-normal text-neutral-muted">Status saat ini</p>
                            <p class="mt-1 font-semibold text-neutral-text">{{ shipmentStatusLabel(currentShipmentStatus) }}</p>
                        </div>
                        <StatusBadge :status="selectedOrder.order_status" />
                    </div>
                    <div v-if="actionStatusOptions.length" class="mt-3 flex flex-wrap gap-2">
                        <AppButton
                            v-for="option in actionStatusOptions"
                            :key="option.value"
                            type="button"
                            size="sm"
                            variant="secondary"
                            @click="chooseStatus(option.value)"
                        >
                            {{ option.label }}
                        </AppButton>
                    </div>
                </div>

                <div class="grid gap-4">
                    <FormSelect id="shipment_status" v-model="shipmentForm.status" label="Status pengiriman" :options="selectedOrder ? availableStatusOptions : formStatusOptions" :error="shipmentForm.errors.status" required />
                    <FormInput id="scheduled_at" v-model="shipmentForm.scheduled_at" type="datetime-local" label="Jadwal pengiriman" :error="shipmentForm.errors.scheduled_at" />
                    <FormInput id="delivered_at" v-model="shipmentForm.delivered_at" type="datetime-local" label="Tanggal terkirim" :error="shipmentForm.errors.delivered_at" />
                    <FormInput id="driver_name" v-model="shipmentForm.driver_name" label="Nama petugas" :error="shipmentForm.errors.driver_name" />
                    <FormInput id="driver_phone" v-model="shipmentForm.driver_phone" label="Nomor petugas" :error="shipmentForm.errors.driver_phone" />
                    <FormInput id="vehicle_note" v-model="shipmentForm.vehicle_note" label="Catatan kendaraan" :error="shipmentForm.errors.vehicle_note" />
                    <label class="block" for="shipping_note">
                        <span class="text-sm font-medium text-neutral-text">Catatan pengiriman</span>
                        <textarea id="shipping_note" v-model="shipmentForm.shipping_note" rows="3" class="mt-1 block w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                        <p v-if="shipmentForm.errors.shipping_note" class="mt-1 text-sm text-danger">{{ shipmentForm.errors.shipping_note }}</p>
                    </label>
                </div>
                <p v-if="shipmentForm.errors.order" class="mt-2 text-sm text-danger">{{ shipmentForm.errors.order }}</p>
                <div class="mt-4 flex gap-2">
                    <AppButton type="submit" :disabled="!selectedOrder" :loading="shipmentForm.processing">
                        <Truck class="h-4 w-4" />
                        Simpan Shipment
                    </AppButton>
                    <AppButton v-if="selectedOrder" type="button" variant="secondary" @click="closeShipmentModal">Batal</AppButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
