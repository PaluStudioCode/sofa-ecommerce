<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import MapPickerShell from '@/Components/UI/MapPickerShell.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { CreditCard, MapPinned, PackageCheck, Truck } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    order: { type: Object, required: true },
    orderStatusOptions: { type: Array, default: () => [] },
});

const page = usePage();
const statusForm = useForm({
    order_status: props.order.order_status,
});

const itemColumns = [
    { key: 'product_name', label: 'Produk' },
    { key: 'variant_name', label: 'Varian' },
    { key: 'quantity', label: 'Qty' },
    { key: 'product_price', label: 'Harga' },
    { key: 'subtotal', label: 'Subtotal' },
    { key: 'current_variant', label: 'Stok Saat Ini' },
];

const paymentColumns = [
    { key: 'attempt_number', label: 'Attempt' },
    { key: 'midtrans_order_id', label: 'Midtrans Order' },
    { key: 'status', label: 'Status' },
    { key: 'gross_amount', label: 'Gross' },
    { key: 'paid_at', label: 'Dibayar' },
];

const mapLabel = computed(() => `${props.order.shipping_latitude}, ${props.order.shipping_longitude}`);

function updateStatus() {
    statusForm.put(route('admin.orders.update', props.order.id), { preserveScroll: true });
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

function rawPreview(raw) {
    if (!raw) return '-';

    return JSON.stringify(raw, null, 2);
}
</script>

<template>
    <Head :title="`Pesanan ${order.order_number}`" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="order.order_number">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-neutral-text">{{ order.order_number }}</h2>
                <p class="mt-1 text-sm text-neutral-muted">{{ order.customer_name }} - {{ order.customer_phone }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <StatusBadge :status="order.order_status" />
                    <StatusBadge :status="order.payment_status" />
                    <StatusBadge :status="order.shipment_status" />
                </div>
            </div>
            <form class="rounded-md border border-neutral-border bg-white p-4" @submit.prevent="updateStatus">
                <div class="grid gap-3 sm:grid-cols-[220px_auto] sm:items-end">
                    <FormSelect id="order_status_update" v-model="statusForm.order_status" label="Ubah status pesanan" :options="orderStatusOptions" :error="statusForm.errors.order_status" />
                    <AppButton type="submit" :loading="statusForm.processing">Simpan</AppButton>
                </div>
            </form>
        </div>

        <Alert v-if="page.props.flash?.success" tone="success" class="mb-4">{{ page.props.flash.success }}</Alert>
        <Alert v-if="page.props.flash?.error" tone="danger" class="mb-4">{{ page.props.flash.error }}</Alert>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="grid gap-5">
                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Timeline</h3>
                    <ol class="mt-4 grid gap-4">
                        <li v-for="step in order.timeline" :key="step.label" class="grid grid-cols-[28px_1fr] gap-3">
                            <span
                                class="mt-1 grid h-7 w-7 place-items-center rounded-full border text-xs font-bold"
                                :class="{
                                    'border-green-200 bg-green-50 text-success': step.state === 'completed',
                                    'border-yellow-200 bg-primary-soft text-neutral-text': step.state === 'current',
                                    'border-neutral-border bg-neutral-light text-neutral-muted': step.state === 'pending',
                                }"
                            >
                                <PackageCheck v-if="step.state === 'completed'" class="h-4 w-4" />
                                <span v-else class="h-2 w-2 rounded-full bg-current" />
                            </span>
                            <div>
                                <p class="font-semibold text-neutral-text">{{ step.label }}</p>
                                <p class="text-sm text-neutral-muted">{{ step.description }}</p>
                            </div>
                        </li>
                    </ol>
                </section>

                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="mb-4 font-semibold text-neutral-text">Item pesanan</h3>
                    <DataTable :columns="itemColumns" :rows="order.items">
                        <template #cell-variant_name="{ row }">
                            <div>
                                <p class="font-semibold text-neutral-text">{{ row.variant_name || '-' }}</p>
                                <p class="text-xs text-neutral-muted">{{ [row.variant_sku, row.variant_size, row.variant_material, row.variant_color].filter(Boolean).join(' / ') }}</p>
                            </div>
                        </template>
                        <template #cell-product_price="{ value }">{{ formatRupiah(value) }}</template>
                        <template #cell-subtotal="{ value }">{{ formatRupiah(value) }}</template>
                        <template #cell-current_variant="{ value }">
                            <div v-if="value" class="text-xs text-neutral-muted">
                                <p>Stok {{ value.stock }} / reserved {{ value.reserved_stock }}</p>
                                <StatusBadge :status="value.status" />
                            </div>
                            <span v-else>-</span>
                        </template>
                    </DataTable>
                </section>

                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <CreditCard class="h-5 w-5 text-primary-hover" />
                        <h3 class="font-semibold text-neutral-text">Payment attempts</h3>
                    </div>
                    <DataTable :columns="paymentColumns" :rows="order.payments">
                        <template #cell-attempt_number="{ row }">
                            <Link :href="route('admin.payments.show', row.id)" class="font-semibold text-neutral-text hover:text-primary-hover">
                                #{{ row.attempt_number }}
                            </Link>
                        </template>
                        <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
                        <template #cell-gross_amount="{ value }">{{ formatRupiah(value) }}</template>
                        <template #cell-paid_at="{ value }">{{ formatDate(value) }}</template>
                    </DataTable>
                </section>
            </div>

            <aside class="grid h-fit gap-5">
                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Ringkasan total</h3>
                    <div class="mt-4 grid gap-3 text-sm">
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Subtotal</span><span class="font-semibold">{{ formatRupiah(order.subtotal_amount) }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Diskon</span><span class="font-semibold">-{{ formatRupiah(order.discount_amount) }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Ongkir</span><span class="font-semibold">{{ formatRupiah(order.shipping_cost) }}</span></div>
                        <div v-if="order.voucher" class="flex justify-between gap-3"><span class="text-neutral-muted">Voucher</span><StatusBadge :status="order.voucher.status" :label="order.voucher.code" /></div>
                        <div class="flex justify-between gap-3 border-t border-neutral-border pt-3 text-base"><span class="font-semibold">Total</span><span class="font-bold">{{ formatRupiah(order.total_amount) }}</span></div>
                    </div>
                </section>

                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Alamat pelanggan</h3>
                    <div class="mt-3 grid gap-2 text-sm text-neutral-muted">
                        <p class="font-semibold text-neutral-text">{{ order.customer_name }} - {{ order.customer_phone }}</p>
                        <p>{{ order.customer_email }}</p>
                        <p>{{ order.shipping_address }}</p>
                        <p>{{ [order.shipping_city, order.shipping_district, order.shipping_postal_code].filter(Boolean).join(', ') }}</p>
                        <p v-if="order.shipping_note" class="rounded-md bg-neutral-light p-3">{{ order.shipping_note }}</p>
                        <p v-if="order.store">Toko layanan: <span class="font-semibold text-neutral-text">{{ order.store.name }}</span></p>
                    </div>
                </section>

                <MapPickerShell title="Lokasi pengiriman" :address="mapLabel">
                    <div class="relative grid h-full min-h-56 w-full place-items-center overflow-hidden rounded-md bg-[#eef4f0]">
                        <div class="absolute inset-0 grid grid-cols-4 grid-rows-4 opacity-40">
                            <span v-for="line in 16" :key="line" class="border border-white/70" />
                        </div>
                        <div class="relative grid h-14 w-14 place-items-center rounded-full border-2 border-info bg-blue-100/80">
                            <MapPinned class="h-7 w-7 text-info" />
                        </div>
                        <div class="absolute bottom-3 left-3 right-3 rounded-md bg-white/90 px-3 py-2 text-xs text-neutral-muted shadow-sm">
                            {{ mapLabel }}
                        </div>
                    </div>
                </MapPickerShell>

                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <div class="mb-3 flex items-center gap-2">
                        <Truck class="h-5 w-5 text-info" />
                        <h3 class="font-semibold text-neutral-text">Shipment</h3>
                    </div>
                    <div v-if="order.shipment" class="grid gap-2 text-sm text-neutral-muted">
                        <div class="flex justify-between gap-3"><span>Status</span><StatusBadge :status="order.shipment.status" /></div>
                        <div class="flex justify-between gap-3"><span>Jadwal</span><span>{{ formatDate(order.shipment.scheduled_at) }}</span></div>
                        <div class="flex justify-between gap-3"><span>Terkirim</span><span>{{ formatDate(order.shipment.delivered_at) }}</span></div>
                        <div class="flex justify-between gap-3"><span>Petugas</span><span>{{ order.shipment.driver_name || '-' }}</span></div>
                        <p v-if="order.shipment.shipping_note" class="rounded-md bg-neutral-light p-3">{{ order.shipment.shipping_note }}</p>
                    </div>
                    <p v-else class="text-sm text-neutral-muted">Shipment belum dibuat.</p>
                </section>

                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Raw response terbatas</h3>
                    <pre class="mt-3 max-h-72 overflow-auto rounded-md bg-neutral-light p-3 text-xs text-neutral-muted">{{ rawPreview(order.payments[0]?.raw_response_preview) }}</pre>
                </section>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
