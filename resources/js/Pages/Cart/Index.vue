<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ShoppingBag, Trash2 } from '@lucide/vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import QuantityStepper from '@/Components/UI/QuantityStepper.vue';
import { useConfirm } from '@/Composables/useFeedback';

defineProps({
    items: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
});

const { confirm } = useConfirm();
const sofaFallback = 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80';

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}

function updateQuantity(item, quantity) {
    router.patch(route('cart.update', item.id), { quantity }, {
        preserveScroll: true,
    });
}

async function removeItem(item) {
    if (await confirm({
        title: 'Hapus item?',
        message: `${item.product_name} akan dihapus dari keranjang.`,
        confirmText: 'Hapus',
        tone: 'danger',
    })) {
        router.delete(route('cart.destroy', item.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Keranjang" />

    <PublicLayout>
        <section class="bg-neutral-light py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-neutral-text">Keranjang</h1>
                        <p class="mt-1 text-sm text-neutral-muted">Kelola produk sebelum lanjut checkout.</p>
                    </div>
                    <AppButton href="/catalog" variant="secondary">
                        <ShoppingBag class="h-4 w-4" />
                        Tambah Produk
                    </AppButton>
                </div>

                <EmptyState v-if="items.length === 0" title="Keranjang masih kosong" message="Pilih sofa dari katalog untuk mulai checkout.">
                    <template #actions>
                        <AppButton href="/catalog">Lihat Katalog</AppButton>
                    </template>
                </EmptyState>

                <div v-else class="grid gap-5 lg:grid-cols-[1fr_360px]">
                    <div class="grid gap-3">
                        <article v-for="item in items" :key="item.id" class="rounded-md border border-neutral-border bg-white p-4">
                            <div class="grid gap-4 sm:grid-cols-[112px_1fr_auto]">
                                <Link :href="route('products.show', item.product_slug)" class="block overflow-hidden rounded-md border border-neutral-border bg-neutral-light">
                                    <img :src="item.image_url || sofaFallback" :alt="item.product_name" class="aspect-square w-full object-cover" />
                                </Link>

                                <div>
                                    <div class="flex flex-wrap items-start gap-2">
                                        <div class="min-w-0 flex-1">
                                            <Link :href="route('products.show', item.product_slug)" class="font-semibold text-neutral-text hover:text-primary-hover">
                                                {{ item.product_name }}
                                            </Link>
                                            <p class="mt-1 text-sm text-neutral-muted">{{ item.category || 'Tanpa kategori' }}</p>
                                        </div>
                                    </div>

                                    <dl class="mt-3 grid gap-2 text-sm text-neutral-muted sm:grid-cols-2">
                                        <div><dt class="font-medium text-neutral-text">Varian</dt><dd>{{ item.variant_name || item.sku || 'Standar' }}</dd></div>
                                        <div><dt class="font-medium text-neutral-text">Spesifikasi</dt><dd>{{ [item.size, item.material, item.color].filter(Boolean).join(' / ') || '-' }}</dd></div>
                                        <div><dt class="font-medium text-neutral-text">Harga saat ini</dt><dd>{{ formatRupiah(item.unit_price) }}</dd></div>
                                        <div><dt class="font-medium text-neutral-text">Stok tersedia</dt><dd>{{ item.available_stock }}</dd></div>
                                    </dl>

                                    <Alert v-if="item.warning" tone="warning" class="mt-3">{{ item.warning }}</Alert>
                                </div>

                                <div class="flex flex-row items-center justify-between gap-3 sm:flex-col sm:items-end">
                                    <QuantityStepper :model-value="item.quantity" :min="1" :max="Math.max(1, item.available_stock)" @update:model-value="updateQuantity(item, $event)" />
                                    <p class="text-sm font-semibold text-neutral-text">{{ formatRupiah(item.subtotal) }}</p>
                                    <button type="button" class="inline-grid h-10 w-10 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="removeItem(item)">
                                        <Trash2 class="h-4 w-4" />
                                        <span class="sr-only">Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>

                    <aside class="h-fit rounded-md border border-neutral-border bg-white p-5">
                        <h2 class="text-lg font-semibold text-neutral-text">Ringkasan</h2>
                        <div class="mt-4 grid gap-3 text-sm">
                            <div class="flex justify-between gap-3">
                                <span class="text-neutral-muted">Item</span>
                                <span class="font-semibold text-neutral-text">{{ summary.items_count }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-neutral-muted">Total jumlah</span>
                                <span class="font-semibold text-neutral-text">{{ summary.total_quantity }}</span>
                            </div>
                            <div class="flex justify-between gap-3 border-t border-neutral-border pt-3">
                                <span class="text-neutral-muted">Subtotal produk</span>
                                <span class="font-semibold text-neutral-text">{{ formatRupiah(summary.subtotal) }}</span>
                            </div>
                        </div>

                        <Alert v-if="!summary.can_checkout" tone="warning" class="mt-4">
                            Keranjang perlu berisi produk aktif dengan stok cukup sebelum checkout.
                        </Alert>

                        <AppButton class="mt-4 w-full" :href="summary.can_checkout ? route('checkout.index') : null" :disabled="!summary.can_checkout">
                            Lanjut Checkout
                        </AppButton>
                    </aside>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
