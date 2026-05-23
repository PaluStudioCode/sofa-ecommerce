<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, PackageCheck, ReceiptText, Truck } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    order: { type: Object, required: true },
    orderStatusOptions: { type: Array, default: () => [] },
});

const statusForm = useForm({
    order_status: props.order.order_status,
});

const latestPayment = computed(() => props.order.payments?.[0] || null);
const hasShipment = computed(() => Boolean(props.order.shipment));

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
</script>

<template>
    <Head :title="`Pesanan ${order.order_number}`" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="order.order_number">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-neutral-text">{{ order.order_number }}</h2>
                <p class="mt-1 text-sm text-neutral-muted">{{ order.customer_name }} - {{ order.customer_phone }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <StatusBadge :status="order.order_status" />
                    <StatusBadge :status="order.payment_status" />
                    <StatusBadge :status="order.shipment_status" />
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <AppButton :href="route('admin.orders.index')" variant="secondary">
                    <ArrowLeft class="h-4 w-4" />
                    Kembali
                </AppButton>
            </div>
        </div>

        <div class="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-md border border-neutral-border bg-white p-4">
                <p class="text-sm text-neutral-muted">Total</p>
                <p class="mt-2 text-xl font-bold text-neutral-text">{{ formatRupiah(order.total_amount) }}</p>
            </section>
            <section class="rounded-md border border-neutral-border bg-white p-4">
                <p class="text-sm text-neutral-muted">Pembayaran</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <StatusBadge :status="order.payment_status" />
                    <span v-if="latestPayment" class="text-sm font-semibold text-neutral-text">#{{ latestPayment.attempt_number }}</span>
                </div>
            </section>
            <section class="rounded-md border border-neutral-border bg-white p-4">
                <p class="text-sm text-neutral-muted">Pengiriman</p>
                <div class="mt-2"><StatusBadge :status="order.shipment_status" /></div>
            </section>
            <section class="rounded-md border border-neutral-border bg-white p-4">
                <p class="text-sm text-neutral-muted">Item</p>
                <p class="mt-2 text-xl font-bold text-neutral-text">{{ order.items_count }}</p>
            </section>
        </div>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="grid min-w-0 content-start gap-5">
                <section class="h-fit rounded-md border border-neutral-border bg-white p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <ReceiptText class="h-5 w-5 text-info" />
                        <h3 class="font-semibold text-neutral-text">Item Pesanan</h3>
                    </div>

                    <div class="grid gap-3">
                        <article v-for="item in order.items" :key="item.id" class="rounded-md border border-neutral-border p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div class="min-w-0">
                                    <p class="font-semibold text-neutral-text">{{ item.product_name }}</p>
                                    <p class="mt-1 text-sm text-neutral-muted">
                                        {{ [item.variant_name, item.variant_sku, item.variant_size, item.variant_material, item.variant_color].filter(Boolean).join(' / ') || 'Tanpa varian' }}
                                    </p>
                                    <div v-if="item.current_variant" class="mt-3 flex flex-wrap items-center gap-2 text-xs text-neutral-muted">
                                        <span>Stok {{ item.current_variant.stock }}</span>
                                        <span>Reserved {{ item.current_variant.reserved_stock }}</span>
                                        <StatusBadge :status="item.current_variant.status" />
                                    </div>
                                </div>
                                <div class="shrink-0 text-left md:text-right">
                                    <p class="text-sm text-neutral-muted">{{ item.quantity }} x {{ formatRupiah(item.product_price) }}</p>
                                    <p class="mt-1 font-semibold text-neutral-text">{{ formatRupiah(item.subtotal) }}</p>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="h-fit rounded-md border border-neutral-border bg-white p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <CreditCard class="h-5 w-5 text-primary-hover" />
                        <h3 class="font-semibold text-neutral-text">Pembayaran</h3>
                    </div>

                    <div v-if="order.payments.length" class="grid gap-3">
                        <article v-for="payment in order.payments" :key="payment.id" class="rounded-md border border-neutral-border p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-neutral-text">Attempt #{{ payment.attempt_number }}</p>
                                        <StatusBadge :status="payment.status" />
                                    </div>
                                    <dl class="mt-3 grid gap-2 text-sm md:grid-cols-2">
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
                    <p v-else class="text-sm text-neutral-muted">Belum ada payment attempt.</p>
                </section>

                <section class="h-fit rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Alamat Pengiriman</h3>
                    <div class="mt-4 grid gap-2 text-sm text-neutral-muted">
                        <p class="font-semibold text-neutral-text">{{ order.customer_name }} - {{ order.customer_phone }}</p>
                        <p>{{ order.customer_email }}</p>
                        <p>{{ order.shipping_address }}</p>
                        <p>{{ [order.shipping_city, order.shipping_district, order.shipping_postal_code].filter(Boolean).join(', ') }}</p>
                        <p v-if="order.shipping_note" class="rounded-md bg-neutral-light p-3">{{ order.shipping_note }}</p>
                        <p v-if="order.store">Titik asal: <span class="font-semibold text-neutral-text">{{ order.store.name }}</span></p>
                    </div>
                </section>
            </div>

            <aside class="grid h-fit content-start gap-5">
                <section class="h-fit rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Ubah Status</h3>
                    <form class="mt-4 grid gap-3" @submit.prevent="updateStatus">
                        <FormSelect id="order_status_update" v-model="statusForm.order_status" label="Status pesanan" :options="orderStatusOptions" :error="statusForm.errors.order_status" />
                        <AppButton type="submit" :loading="statusForm.processing">Simpan</AppButton>
                    </form>
                </section>

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

                <section class="h-fit rounded-md border border-neutral-border bg-white p-5">
                    <div class="mb-3 flex items-center gap-2">
                        <Truck class="h-5 w-5 text-info" />
                        <h3 class="font-semibold text-neutral-text">Pengiriman</h3>
                    </div>
                    <div v-if="hasShipment" class="grid gap-2 text-sm text-neutral-muted">
                        <div class="flex justify-between gap-3"><span>Status</span><StatusBadge :status="order.shipment.status" /></div>
                        <div class="flex justify-between gap-3"><span>Jadwal</span><span>{{ formatDate(order.shipment.scheduled_at) }}</span></div>
                        <div class="flex justify-between gap-3"><span>Terkirim</span><span>{{ formatDate(order.shipment.delivered_at) }}</span></div>
                        <div class="flex justify-between gap-3"><span>Petugas</span><span>{{ order.shipment.driver_name || '-' }}</span></div>
                        <p v-if="order.shipment.shipping_note" class="rounded-md bg-neutral-light p-3">{{ order.shipment.shipping_note }}</p>
                    </div>
                    <p v-else class="text-sm text-neutral-muted">Belum ada pengiriman.</p>
                </section>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
