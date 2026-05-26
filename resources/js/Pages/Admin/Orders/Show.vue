<script setup>
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import LeafletLocationPreview from '@/Components/UI/LeafletLocationPreview.vue';
import Modal from '@/Components/Modal.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, MapPinned, PackageCheck, ReceiptText, Truck, X } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    order: { type: Object, required: true },
});

const mapModalOpen = ref(false);
const shipmentModalOpen = ref(false);
const hasShipment = computed(() => Boolean(props.order.shipment));
const hasShippingCoordinates = computed(() => Number.isFinite(Number(props.order.shipping_latitude)) && Number.isFinite(Number(props.order.shipping_longitude)));
const sofaFallback = 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80';

const statusLabels = {
    diproses: 'Diproses',
    dalam_perjalanan: 'Dalam perjalanan',
    barang_diterima: 'Barang diterima',
};
const orderStatusOptions = [
    { value: 'diproses', label: 'Diproses' },
    { value: 'dalam_perjalanan', label: 'Dalam perjalanan' },
    { value: 'barang_diterima', label: 'Barang diterima' },
];
const availableOrderStatusOptions = computed(() => {
    const allowed = props.order.allowed_order_statuses || [props.order.order_status];

    return orderStatusOptions.filter((option) => allowed.includes(option.value));
});
const shipmentForm = useForm({
    order_status: 'diproses',
    scheduled_at: '',
    delivered_at: '',
    driver_name: '',
    driver_phone: '',
    vehicle_note: '',
    shipping_note: '',
});
const shouldShowDeliveredAt = computed(() => shipmentForm.order_status === 'barang_diterima');

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

function formatDateOnly(value) {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
    }).format(new Date(value));
}

function formatKilometer(value) {
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 2,
    }).format(value || 0);
}

function dateInputValue(value) {
    if (!value) {
        return '';
    }

    return String(value).slice(0, 10);
}

function todayInputValue() {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());

    return date.toISOString().slice(0, 10);
}

function orderStatusLabel(status) {
    return statusLabels[status] || status;
}

function fillShipmentForm() {
    shipmentForm.order_status = props.order.order_status || 'diproses';
    shipmentForm.scheduled_at = dateInputValue(props.order.shipment?.scheduled_at);
    shipmentForm.delivered_at = dateInputValue(props.order.shipment?.delivered_at);
    shipmentForm.driver_name = props.order.shipment?.driver_name || '';
    shipmentForm.driver_phone = props.order.shipment?.driver_phone || '';
    shipmentForm.vehicle_note = props.order.shipment?.vehicle_note || '';
    shipmentForm.shipping_note = props.order.shipment?.shipping_note || '';
    shipmentForm.clearErrors();
}

function openShipmentModal() {
    fillShipmentForm();
    shipmentModalOpen.value = true;
}

function closeShipmentModal() {
    if (!shipmentForm.processing) {
        shipmentModalOpen.value = false;
    }
}

function submitShipment() {
    shipmentForm.put(route('admin.shipments.update', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            shipmentForm.clearErrors();
            shipmentModalOpen.value = false;
        },
    });
}

watch(() => shipmentForm.order_status, (status) => {
    if (!shipmentModalOpen.value) {
        return;
    }

    if (['dalam_perjalanan', 'barang_diterima'].includes(status) && !shipmentForm.scheduled_at) {
        shipmentForm.scheduled_at = todayInputValue();
    }

    if (status === 'barang_diterima' && !shipmentForm.delivered_at) {
        shipmentForm.delivered_at = todayInputValue();
    }

    if (status !== 'barang_diterima' && shipmentForm.delivered_at) {
        shipmentForm.delivered_at = '';
    }
});
</script>

<template>
    <Head :title="`Pesanan ${order.order_number}`" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="order.order_number">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-neutral-text">{{ order.order_number }}</h2>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <AppButton :href="route('admin.orders.index')" variant="secondary">
                    <ArrowLeft class="h-4 w-4" />
                    Kembali
                </AppButton>
            </div>
        </div>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="grid min-w-0 content-start gap-5">
                <section class="h-fit rounded-md border border-neutral-border bg-white p-4">
                    <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <ReceiptText class="h-5 w-5 text-info" />
                            <h3 class="font-semibold text-neutral-text">Item Pesanan</h3>
                        </div>
                        <p class="text-sm text-neutral-muted">{{ order.items.length }} item</p>
                    </div>

                    <div class="max-h-[38vh] overflow-y-auto pr-1">
                        <div class="grid gap-2">
                            <article v-for="item in order.items" :key="item.id" class="rounded-md border border-neutral-border p-2.5">
                                <div class="grid grid-cols-[56px_minmax(0,1fr)] gap-2.5 md:grid-cols-[56px_minmax(0,1fr)_auto] md:items-center">
                                    <img :src="item.image_url || sofaFallback" :alt="item.product_name" class="h-14 w-14 rounded-md object-cover" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-neutral-text">{{ item.product_name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-neutral-muted">{{ item.variant_name || 'Varian standar' }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-muted">
                                            <span v-if="item.variant_sku">{{ item.variant_sku }}</span>
                                            <span>{{ [item.variant_size, item.variant_material, item.variant_color].filter(Boolean).join(' / ') || 'Tanpa detail varian' }}</span>
                                            <span v-if="item.current_variant">Stok {{ item.current_variant.stock }}</span>
                                            <span v-if="item.current_variant">Reserved {{ item.current_variant.reserved_stock }}</span>
                                            <StatusBadge v-if="item.current_variant" :status="item.current_variant.status" />
                                        </div>
                                    </div>
                                    <div class="col-span-2 shrink-0 text-left md:col-span-1 md:text-right">
                                        <p class="text-xs text-neutral-muted">{{ item.quantity }} x {{ formatRupiah(item.product_price) }}</p>
                                        <p class="mt-0.5 text-sm font-semibold text-neutral-text">{{ formatRupiah(item.subtotal) }}</p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="h-fit rounded-md border border-neutral-border bg-white p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <CreditCard class="h-5 w-5 text-primary-hover" />
                        <h3 class="font-semibold text-neutral-text">Pembayaran</h3>
                    </div>

                    <div v-if="order.payments.length" class="max-h-[42vh] overflow-y-auto pr-1">
                        <div class="grid gap-3">
                            <article v-for="payment in order.payments" :key="payment.id" class="rounded-md border border-neutral-border p-3">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-neutral-text">Attempt #{{ payment.attempt_number }}</p>
                                            <StatusBadge :status="payment.status" />
                                        </div>
                                        <dl class="mt-2 grid gap-2 text-sm md:grid-cols-2">
                                            <div>
                                                <dt class="text-neutral-muted">Midtrans order</dt>
                                                <dd class="break-all font-semibold text-neutral-text">{{ payment.midtrans_order_id }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-neutral-muted">Transaction ID</dt>
                                                <dd class="break-all font-semibold text-neutral-text">{{ payment.midtrans_transaction_id || '-' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-neutral-muted">Metode</dt>
                                                <dd class="font-semibold text-neutral-text">{{ payment.payment_type || '-' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-neutral-muted">Dibayar</dt>
                                                <dd class="font-semibold text-neutral-text">{{ formatDate(payment.paid_at) }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <div class="shrink-0 text-left md:text-right">
                                        <p class="text-sm text-neutral-muted">Gross</p>
                                        <p class="font-semibold text-neutral-text">{{ formatRupiah(payment.gross_amount) }}</p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                    <p v-else class="text-sm text-neutral-muted">Belum ada payment attempt.</p>
                </section>

                <section class="h-fit rounded-md border border-neutral-border bg-white p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="font-semibold text-neutral-text">Alamat Pengiriman</h3>
                        <AppButton v-if="hasShippingCoordinates" type="button" variant="secondary" @click="mapModalOpen = true">
                            <MapPinned class="h-4 w-4" />
                            Lihat Maps
                        </AppButton>
                    </div>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-neutral-muted">Nama penerima</dt>
                            <dd class="font-semibold text-neutral-text">{{ order.customer_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-neutral-muted">No telepon</dt>
                            <dd class="font-semibold text-neutral-text">{{ order.customer_phone }}</dd>
                        </div>
                        <div>
                            <dt class="text-neutral-muted">Email akun</dt>
                            <dd class="text-neutral-text">{{ order.customer_email }}</dd>
                        </div>
                        <div>
                            <dt class="text-neutral-muted">Alamat dari maps</dt>
                            <dd class="text-neutral-text">{{ order.shipping_address }}</dd>
                        </div>
                        <div v-if="[order.shipping_city, order.shipping_district, order.shipping_postal_code].filter(Boolean).length">
                            <dt class="text-neutral-muted">Area</dt>
                            <dd class="text-neutral-text">{{ [order.shipping_city, order.shipping_district, order.shipping_postal_code].filter(Boolean).join(', ') }}</dd>
                        </div>
                        <div v-if="order.shipping_note">
                            <dt class="text-neutral-muted">Detail alamat / catatan</dt>
                            <dd class="whitespace-pre-line rounded-md bg-neutral-light p-3 text-neutral-text">{{ order.shipping_note }}</dd>
                        </div>
                        <div v-if="order.store">
                            <dt class="text-neutral-muted">Titik asal</dt>
                            <dd class="font-semibold text-neutral-text">{{ order.store.name }}</dd>
                            <dd v-if="order.store.origin_address" class="mt-1 text-neutral-muted">{{ order.store.origin_address }}</dd>
                        </div>
                        <div v-if="order.store?.distance_km">
                            <dt class="text-neutral-muted">Detail ongkir</dt>
                            <dd class="mt-1 grid gap-2 rounded-md bg-neutral-light p-3 text-neutral-text">
                                <span class="flex justify-between gap-3">
                                    <span>Jarak jalan</span>
                                    <span class="font-semibold">{{ formatKilometer(order.store.distance_km) }} km</span>
                                </span>
                                <span class="flex justify-between gap-3">
                                    <span>Jarak ditagihkan</span>
                                    <span class="font-semibold">{{ formatKilometer(order.store.billable_distance_km) }} km</span>
                                </span>
                                <span class="flex justify-between gap-3">
                                    <span>Tarif per KM</span>
                                    <span class="font-semibold">{{ formatRupiah(order.store.shipping_cost_per_km) }}</span>
                                </span>
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>

            <aside class="grid h-fit content-start gap-5 xl:sticky xl:top-24">
                <section class="h-fit rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Ringkasan Total</h3>
                    <div class="mt-4 grid gap-3 text-sm">
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Subtotal</span><span class="font-semibold">{{ formatRupiah(order.subtotal_amount) }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Diskon</span><span class="font-semibold">-{{ formatRupiah(order.discount_amount) }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Ongkir</span><span class="font-semibold">{{ formatRupiah(order.shipping_cost) }}</span></div>
                        <div v-if="order.voucher" class="flex justify-between gap-3"><span class="text-neutral-muted">Voucher</span><StatusBadge :status="order.voucher.status" :label="order.voucher.code" /></div>
                        <div class="flex justify-between gap-3 border-t border-neutral-border pt-3 text-base"><span class="font-semibold">Total</span><span class="font-bold">{{ formatRupiah(order.total_amount) }}</span></div>
                    </div>
                </section>

                <section class="h-fit rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Timeline</h3>
                    <ol class="mt-4 grid">
                        <li v-for="(step, index) in order.timeline" :key="step.label" class="relative grid grid-cols-[28px_1fr] gap-3 pb-4 last:pb-0">
                            <span v-if="index < order.timeline.length - 1" class="absolute bottom-0 left-[13px] top-8 w-px bg-neutral-border" />
                            <span
                                class="relative z-10 mt-1 grid h-7 w-7 place-items-center rounded-full border text-xs font-bold"
                                :class="{
                                    'border-green-200 bg-green-50 text-success': step.state === 'completed',
                                    'border-yellow-200 bg-primary-soft text-neutral-text': step.state === 'current',
                                    'border-red-200 bg-red-50 text-danger': step.state === 'blocked',
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

                <section class="h-fit rounded-md border border-neutral-border bg-white p-5">
                    <div class="mb-3 flex items-center gap-2">
                        <Truck class="h-5 w-5 text-info" />
                        <h3 class="font-semibold text-neutral-text">Pengiriman</h3>
                    </div>
                    <div v-if="hasShipment" class="grid gap-2 text-sm text-neutral-muted">
                        <div class="flex justify-between gap-3"><span>Status</span><StatusBadge :status="order.order_status" /></div>
                        <div class="flex justify-between gap-3"><span>Jadwal</span><span>{{ formatDateOnly(order.shipment.scheduled_at) }}</span></div>
                        <div class="flex justify-between gap-3"><span>Barang diterima</span><span>{{ formatDateOnly(order.shipment.delivered_at) }}</span></div>
                        <div class="flex justify-between gap-3"><span>Petugas</span><span>{{ order.shipment.driver_name || '-' }}</span></div>
                        <p v-if="order.shipment.shipping_note" class="rounded-md bg-neutral-light p-3">{{ order.shipment.shipping_note }}</p>
                    </div>
                    <p v-else class="text-sm text-neutral-muted">Belum ada pengiriman.</p>
                    <div class="mt-4">
                        <AppButton v-if="order.can_manage_shipment" type="button" class="w-full" @click="openShipmentModal">
                            <Truck class="h-4 w-4" />
                            Kelola Pengiriman
                        </AppButton>
                        <p v-else class="text-sm text-neutral-muted">Pengiriman bisa dikelola setelah pembayaran berhasil.</p>
                    </div>
                </section>
            </aside>
        </div>

        <Modal :show="mapModalOpen" max-width="4xl" @close="mapModalOpen = false">
            <section class="p-5">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-text">Titik Lokasi Pengiriman</h2>
                        <p class="mt-1 text-sm text-neutral-muted">{{ order.customer_name }} - {{ order.customer_phone }}</p>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="mapModalOpen = false">
                        <X class="h-4 w-4" />
                        <span class="sr-only">Tutup</span>
                    </button>
                </div>

                <LeafletLocationPreview
                    :latitude="order.shipping_latitude"
                    :longitude="order.shipping_longitude"
                    :address="order.shipping_address"
                    marker-label="Titik pengiriman"
                />
            </section>
        </Modal>

        <Modal :show="shipmentModalOpen" max-width="3xl" :closeable="!shipmentForm.processing" @close="closeShipmentModal">
            <form class="flex max-h-[calc(100vh-3rem)] flex-col" @submit.prevent="submitShipment">
                <div class="border-b border-neutral-border px-5 py-4 sm:px-6">
                    <div class="flex items-start gap-3">
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-md bg-primary-soft text-neutral-text">
                            <Truck class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-semibold text-neutral-text">Kelola Pengiriman</h3>
                            <p class="mt-1 break-words text-sm leading-5 text-neutral-muted">{{ order.order_number }} - {{ order.shipping_address || 'Alamat belum tersedia' }}</p>
                        </div>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="mb-5 rounded-md border border-neutral-border bg-neutral-light p-4">
                        <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-normal text-neutral-muted">Status order</p>
                                <p class="mt-1 font-semibold text-neutral-text">{{ orderStatusLabel(shipmentForm.order_status) }}</p>
                            </div>
                            <div class="flex items-start sm:justify-end">
                                <StatusBadge :status="shipmentForm.order_status" :label="orderStatusLabel(shipmentForm.order_status)" />
                            </div>
                            <dl class="grid gap-3 text-sm sm:col-span-2 sm:grid-cols-3">
                                <div>
                                    <dt class="text-neutral-muted">Customer</dt>
                                    <dd class="mt-1 font-semibold text-neutral-text">{{ order.customer_name || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-neutral-muted">Telepon</dt>
                                    <dd class="mt-1 font-semibold text-neutral-text">{{ order.customer_phone || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-neutral-muted">Total</dt>
                                    <dd class="mt-1 font-semibold text-neutral-text">{{ formatRupiah(order.total_amount) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div class="grid content-start gap-4">
                            <h4 class="text-sm font-semibold text-neutral-text">Status dan jadwal</h4>
                            <FormSelect id="delivery_order_status" v-model="shipmentForm.order_status" label="Status order" :options="availableOrderStatusOptions" :error="shipmentForm.errors.order_status" required />
                            <FormInput id="delivery_scheduled_at" v-model="shipmentForm.scheduled_at" type="date" label="Tanggal mulai perjalanan" :error="shipmentForm.errors.scheduled_at" />
                            <FormInput v-if="shouldShowDeliveredAt" id="delivery_delivered_at" v-model="shipmentForm.delivered_at" type="date" label="Tanggal barang diterima" :error="shipmentForm.errors.delivered_at" required />
                        </div>

                        <div class="grid content-start gap-4">
                            <h4 class="text-sm font-semibold text-neutral-text">Petugas pengiriman</h4>
                            <FormInput id="delivery_driver_name" v-model="shipmentForm.driver_name" label="Nama petugas" :error="shipmentForm.errors.driver_name" />
                            <FormInput id="delivery_driver_phone" v-model="shipmentForm.driver_phone" label="Nomor petugas" :error="shipmentForm.errors.driver_phone" />
                            <FormInput id="delivery_vehicle_note" v-model="shipmentForm.vehicle_note" label="Catatan kendaraan" :error="shipmentForm.errors.vehicle_note" />
                        </div>

                        <label class="block lg:col-span-2" for="delivery_shipping_note">
                            <span class="text-sm font-medium text-neutral-text">Catatan pengiriman</span>
                            <textarea id="delivery_shipping_note" v-model="shipmentForm.shipping_note" rows="4" class="mt-1 block min-h-28 w-full resize-y rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                            <p v-if="shipmentForm.errors.shipping_note" class="mt-1 text-sm text-danger">{{ shipmentForm.errors.shipping_note }}</p>
                        </label>
                    </div>

                    <p v-if="shipmentForm.errors.order" class="mt-3 text-sm text-danger">{{ shipmentForm.errors.order }}</p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-neutral-border px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <AppButton type="button" variant="secondary" @click="closeShipmentModal">Batal</AppButton>
                    <AppButton type="submit" :loading="shipmentForm.processing">
                        <Truck class="h-4 w-4" />
                        Simpan Pengiriman
                    </AppButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
