<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    payment: { type: Object, required: true },
});

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
    <Head :title="`Payment ${payment.midtrans_order_id}`" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="payment.midtrans_order_id">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-neutral-text">Payment attempt #{{ payment.attempt_number }}</h2>
                <p class="mt-1 text-sm text-neutral-muted">{{ payment.midtrans_transaction_id || 'Transaction ID belum tersedia' }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <StatusBadge :status="payment.status" />
                    <StatusBadge :status="payment.transaction_status" />
                </div>
            </div>
            <AppButton :href="route('admin.payments.index')" variant="secondary">
                <ArrowLeft class="h-4 w-4" />
                Kembali
            </AppButton>
        </div>

        <div class="grid gap-5 lg:grid-cols-[1fr_420px]">
            <section class="rounded-md border border-neutral-border bg-white p-5">
                <h3 class="font-semibold text-neutral-text">Detail Midtrans</h3>
                <dl class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <dt class="text-sm text-neutral-muted">Midtrans order id</dt>
                        <dd class="mt-1 break-all font-semibold text-neutral-text">{{ payment.midtrans_order_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-muted">Transaction id</dt>
                        <dd class="mt-1 break-all font-semibold text-neutral-text">{{ payment.midtrans_transaction_id || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-muted">Payment type</dt>
                        <dd class="mt-1 font-semibold text-neutral-text">{{ payment.payment_type || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-muted">Fraud status</dt>
                        <dd class="mt-1 font-semibold text-neutral-text">{{ payment.fraud_status || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-muted">Gross amount</dt>
                        <dd class="mt-1 font-semibold text-neutral-text">{{ formatRupiah(payment.gross_amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-muted">Dibuat</dt>
                        <dd class="mt-1 font-semibold text-neutral-text">{{ formatDate(payment.created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-muted">Paid at</dt>
                        <dd class="mt-1 font-semibold text-neutral-text">{{ formatDate(payment.paid_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-neutral-muted">Expired at</dt>
                        <dd class="mt-1 font-semibold text-neutral-text">{{ formatDate(payment.expired_at) }}</dd>
                    </div>
                </dl>

                <div class="mt-5">
                    <h3 class="font-semibold text-neutral-text">Raw response terbatas</h3>
                    <pre class="mt-3 max-h-96 overflow-auto rounded-md bg-neutral-light p-4 text-xs text-neutral-muted">{{ rawPreview(payment.raw_response_preview) }}</pre>
                </div>
            </section>

            <aside class="grid h-fit gap-5">
                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Order terkait</h3>
                    <div v-if="payment.order" class="mt-4 grid gap-3 text-sm">
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Nomor</span><span class="font-semibold text-neutral-text">{{ payment.order.order_number }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Customer</span><span class="font-semibold text-neutral-text">{{ payment.order.customer_name }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Telepon</span><span class="font-semibold text-neutral-text">{{ payment.order.customer_phone }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Email</span><span class="font-semibold text-neutral-text">{{ payment.order.customer_email }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Total order</span><span class="font-semibold text-neutral-text">{{ formatRupiah(payment.order.total_amount) }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Order</span><StatusBadge :status="payment.order.order_status" /></div>
                        <div class="flex justify-between gap-3"><span class="text-neutral-muted">Pembayaran</span><StatusBadge :status="payment.order.payment_status" /></div>
                    </div>
                </section>

                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <h3 class="font-semibold text-neutral-text">Keamanan status</h3>
                    <p class="mt-2 text-sm leading-6 text-neutral-muted">Status pembayaran diperbarui otomatis setelah konfirmasi dari Midtrans diterima.</p>
                </section>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
