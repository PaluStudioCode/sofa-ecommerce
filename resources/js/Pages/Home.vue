<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { CreditCard, PackageCheck, Sofa, Truck } from '@lucide/vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';

const props = defineProps({
    sections: { type: Array, default: () => [] },
    featuredProducts: { type: Array, default: () => [] },
    activeVoucher: { type: Object, default: null },
});

const sofaFallback = 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80';

const heroSection = computed(() => props.sections.find((section) => section.section_key === 'hero') || {});
const valueSections = computed(() => props.sections.filter((section) => section.section_key === 'value').slice(0, 3));

const heroImage = computed(() => heroSection.value.image_url || props.featuredProducts[0]?.image_url || sofaFallback);

const shoppingFlow = [
    { label: 'Pilih produk', icon: Sofa },
    { label: 'Checkout', icon: PackageCheck },
    { label: 'Bayar', icon: CreditCard },
    { label: 'Dikirim', icon: Truck },
];

function formatRupiah(value) {
    if (!value) return 'Harga menyusul';

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function priceRange(product) {
    if (!product.min_price) return 'Harga menyusul';
    if (product.min_price === product.max_price) return formatRupiah(product.min_price);

    return `${formatRupiah(product.min_price)} - ${formatRupiah(product.max_price)}`;
}
</script>

<template>
    <Head title="SofaStore" />

    <PublicLayout>
        <section class="bg-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_0.9fr] lg:px-8 lg:py-14">
                <div class="flex flex-col justify-center">
                    <p class="text-sm font-semibold text-neutral-muted">SofaStore</p>
                    <h1 class="mt-3 max-w-3xl text-4xl font-bold leading-tight text-neutral-text sm:text-5xl">
                        {{ heroSection.title || 'Sofa nyaman untuk rumah yang hidup' }}
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-neutral-muted">
                        {{ heroSection.subtitle || 'Pilih sofa, checkout, bayar online, lalu tunggu pengiriman internal toko.' }}
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <AppButton :href="heroSection.button_url || '/catalog'">
                            {{ heroSection.button_label || 'Lihat Katalog' }}
                        </AppButton>
                        <AppButton href="/catalog" variant="secondary">Produk Sofa</AppButton>
                    </div>
                </div>

                <div class="overflow-hidden rounded-md border border-neutral-border bg-neutral-light">
                    <img :src="heroImage" alt="Sofa unggulan SofaStore" class="aspect-[4/3] h-full w-full object-cover" />
                </div>
            </div>
        </section>

        <section v-if="activeVoucher" class="border-y border-neutral-border bg-primary-soft">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <div>
                    <p class="text-sm font-semibold text-neutral-text">{{ activeVoucher.name }}</p>
                    <p class="text-sm text-neutral-muted">{{ activeVoucher.description || 'Voucher aktif tersedia untuk checkout.' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <StatusBadge status="aktif" :label="activeVoucher.code" />
                    <AppButton href="/catalog" variant="secondary">Pakai Voucher</AppButton>
                </div>
            </div>
        </section>

        <section v-if="valueSections.length" class="bg-white py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 md:grid-cols-3">
                    <article v-for="section in valueSections" :key="section.id" class="rounded-md border border-neutral-border bg-white p-5">
                        <h2 class="text-base font-semibold text-neutral-text">{{ section.title }}</h2>
                        <p v-if="section.content || section.subtitle" class="mt-2 text-sm leading-6 text-neutral-muted">
                            {{ section.content || section.subtitle }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="featuredProducts.length" class="bg-neutral-light py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-neutral-text">Produk unggulan</h2>
                        <p class="mt-1 text-sm text-neutral-muted">Pilihan sofa aktif dari toko.</p>
                    </div>
                    <Link href="/catalog" class="text-sm font-semibold text-neutral-text hover:text-primary-hover">Lihat semua</Link>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="product in featuredProducts" :key="product.id" class="overflow-hidden rounded-md border border-neutral-border bg-white">
                        <img :src="product.image_url || sofaFallback" :alt="product.name" class="aspect-[4/3] w-full object-cover" />
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-neutral-text">{{ product.name }}</h3>
                                    <p class="text-sm text-neutral-muted">{{ product.category }}</p>
                                </div>
                                <StatusBadge :status="product.available ? 'aktif' : 'stok_habis'" :label="product.available ? 'Tersedia' : 'Stok habis'" />
                            </div>
                            <p class="mt-3 text-sm font-semibold text-neutral-text">{{ priceRange(product) }}</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="bg-white py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-4">
                    <div v-for="step in shoppingFlow" :key="step.label" class="rounded-md border border-neutral-border bg-white p-4">
                        <component :is="step.icon" class="h-5 w-5 text-info" />
                        <p class="mt-3 text-sm font-semibold text-neutral-text">{{ step.label }}</p>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
