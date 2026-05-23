<script setup>
import { computed, markRaw } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgePercent,
    CheckCircle2,
    Clock3,
    CreditCard,
    PackageCheck,
    Ruler,
    ShieldCheck,
    Sofa,
    Sparkles,
    Truck,
} from '@lucide/vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';

const props = defineProps({
    sections: { type: Array, default: () => [] },
    featuredProducts: { type: Array, default: () => [] },
    activeVoucher: { type: Object, default: null },
});

const sofaFallback = 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1600&q=85';
const roomFallback = 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1400&q=85';
const valueIcons = [markRaw(ShieldCheck), markRaw(Ruler), markRaw(Truck)];

const heroSection = computed(() => props.sections.find((section) => section.section_key === 'hero') || {});
const valueSections = computed(() => props.sections.filter((section) => section.section_key === 'value').slice(0, 3));

const heroImage = computed(() => heroSection.value.image_url || props.featuredProducts[0]?.image_url || sofaFallback);
const heroCopy = computed(() => heroSection.value.subtitle || heroSection.value.title || 'Sofa pilihan untuk ruang keluarga yang terasa rapi, nyaman, dan siap dihuni setiap hari.');
const primaryCtaLabel = computed(() => heroSection.value.button_label || 'Lihat Katalog');
const primaryCtaUrl = computed(() => heroSection.value.button_url || '/catalog');

const defaultValueHighlights = [
    {
        id: 'curated',
        icon: valueIcons[0],
        title: 'Kurasi siap pakai',
        body: 'Produk aktif dipilih dengan varian, stok, dan rentang harga yang jelas sebelum masuk katalog.',
    },
    {
        id: 'measured',
        icon: valueIcons[1],
        title: 'Detail mudah dibandingkan',
        body: 'Ukuran, material, warna, dan harga varian ditata agar keputusan terasa sederhana.',
    },
    {
        id: 'delivery',
        icon: valueIcons[2],
        title: 'Pengiriman terpantau',
        body: 'Checkout, pembayaran, dan proses pengiriman internal berjalan dalam satu alur.',
    },
];

const valueHighlights = computed(() => {
    if (!valueSections.value.length) {
        return defaultValueHighlights;
    }

    return valueSections.value.map((section, index) => ({
        id: section.id,
        icon: valueIcons[index % valueIcons.length],
        title: section.title,
        body: section.content || section.subtitle || 'Dipilih untuk membantu rumah terasa lebih nyaman dan tertata.',
    }));
});

const heroStats = computed(() => [
    { label: 'Produk unggulan', value: props.featuredProducts.length ? `${props.featuredProducts.length}+` : 'Siap' },
    { label: 'Pengiriman', value: 'Internal' },
    { label: 'Pembayaran', value: 'Online' },
]);

const shoppingFlow = [
    { label: 'Pilih sofa', description: 'Bandingkan model, varian, dan ketersediaan stok.', icon: markRaw(Sofa) },
    { label: 'Checkout', description: 'Alamat dan ringkasan belanja terkumpul rapi.', icon: markRaw(PackageCheck) },
    { label: 'Bayar online', description: 'Pembayaran diproses melalui alur yang terhubung.', icon: markRaw(CreditCard) },
    { label: 'Dikirim', description: 'Pesanan masuk ke proses pengiriman toko.', icon: markRaw(Truck) },
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

function discountLabel(voucher) {
    if (!voucher) return '';
    if (voucher.discount_type === 'percentage') {
        return `${Number(voucher.discount_value || 0)}%`;
    }

    return formatRupiah(voucher.discount_value);
}

function formatDate(value) {
    if (!value) return null;

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
}
</script>

<template>
    <Head title="Home" />

    <PublicLayout>
        <section class="relative isolate overflow-hidden bg-neutral-text text-white">
            <img :src="heroImage" alt="Ruang keluarga dengan sofa SofaStore" class="absolute inset-0 -z-20 h-full w-full object-cover" />
            <div class="absolute inset-0 -z-10 bg-neutral-text/70" />

            <div class="mx-auto flex min-h-[calc(100svh-8rem)] max-w-7xl flex-col justify-center px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <p class="text-sm font-semibold text-primary">SofaStore Home</p>
                <h1 class="mt-4 max-w-4xl text-5xl font-bold leading-tight sm:text-6xl lg:text-7xl">SofaStore</h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-white/80 sm:text-lg">
                    {{ heroCopy }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <AppButton :href="primaryCtaUrl" size="lg">
                        {{ primaryCtaLabel }}
                        <ArrowRight class="h-5 w-5" />
                    </AppButton>
                </div>

                <dl class="mt-10 grid max-w-2xl grid-cols-3 gap-4 border-t border-white/25 pt-5">
                    <div v-for="stat in heroStats" :key="stat.label">
                        <dt class="text-xs font-medium text-white/70">{{ stat.label }}</dt>
                        <dd class="mt-1 text-lg font-semibold text-white">{{ stat.value }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section v-if="activeVoucher" class="border-y border-neutral-border bg-primary-soft">
            <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-6 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                <div class="flex gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-md bg-white text-neutral-text">
                        <BadgePercent class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-neutral-muted">Voucher aktif</p>
                        <h2 class="mt-1 text-xl font-bold text-neutral-text">{{ activeVoucher.name }}</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-neutral-muted">
                            {{ activeVoucher.description || `Diskon ${discountLabel(activeVoucher)} untuk pembelian sofa pilihan.` }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <StatusBadge status="aktif" :label="activeVoucher.code" />
                    <span v-if="activeVoucher.minimum_purchase" class="text-sm font-medium text-neutral-muted">
                        Min. {{ formatRupiah(activeVoucher.minimum_purchase) }}
                    </span>
                    <span v-if="formatDate(activeVoucher.end_at)" class="inline-flex items-center gap-2 text-sm font-medium text-neutral-muted">
                        <Clock3 class="h-4 w-4" />
                        {{ formatDate(activeVoucher.end_at) }}
                    </span>
                    <AppButton href="/catalog" variant="secondary">Pakai Voucher</AppButton>
                </div>
            </div>
        </section>

        <section class="bg-white py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold text-info">Belanja lebih tenang</p>
                    <h2 class="mt-2 text-3xl font-bold text-neutral-text">Sofa yang dipilih dengan konteks rumah nyata.</h2>
                    <p class="mt-3 text-base leading-7 text-neutral-muted">
                        Mulai dari sofa keluarga, sudut santai, sampai ruang tamu formal, setiap pilihan dibuat mudah dibandingkan dari foto, harga, dan stoknya.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    <article v-for="item in valueHighlights" :key="item.id" class="border-t border-neutral-border pt-5">
                        <component :is="item.icon" class="h-6 w-6 text-info" />
                        <h3 class="mt-4 text-lg font-semibold text-neutral-text">{{ item.title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-neutral-muted">{{ item.body }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section v-if="featuredProducts.length" class="bg-neutral-light py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-info">Koleksi pilihan</p>
                        <h2 class="mt-2 text-3xl font-bold text-neutral-text">Produk unggulan SofaStore</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-muted">Sofa aktif dengan foto, kategori, stok, dan harga yang siap dibandingkan.</p>
                    </div>
                    <Link href="/catalog" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-text hover:text-primary-hover">
                        Lihat semua
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="product in featuredProducts"
                        :key="product.id"
                        :href="route('products.show', product.slug)"
                        class="group overflow-hidden rounded-md border border-neutral-border bg-white transition hover:border-primary-hover hover:shadow-sm"
                    >
                        <div class="relative bg-neutral-border">
                            <img :src="product.image_url || sofaFallback" :alt="product.name" class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm text-neutral-muted">{{ product.category || 'Tanpa kategori' }}</p>
                                    <h3 class="mt-1 text-lg font-semibold text-neutral-text">{{ product.name }}</h3>
                                </div>
                                <StatusBadge :status="product.available ? 'aktif' : 'stok_habis'" :label="product.available ? 'Tersedia' : 'Stok habis'" />
                            </div>
                            <p class="mt-4 text-sm font-semibold text-neutral-text">{{ priceRange(product) }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <section class="bg-white py-14">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:items-center lg:px-8">
                <div class="overflow-hidden rounded-md">
                    <img :src="heroSection.image_url || roomFallback" alt="Inspirasi ruang tamu SofaStore" class="aspect-[4/3] w-full object-cover" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-info">Nyaman dari awal</p>
                    <h2 class="mt-2 text-3xl font-bold text-neutral-text">Ruang yang siap menjadi pusat aktivitas rumah.</h2>
                    <p class="mt-4 text-base leading-7 text-neutral-muted">
                        Temukan sofa yang terasa pas untuk menerima tamu, bersantai setelah bekerja, atau menghabiskan akhir pekan bersama keluarga.
                    </p>
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="flex gap-3">
                            <CheckCircle2 class="mt-1 h-5 w-5 shrink-0 text-success" />
                            <p class="text-sm leading-6 text-neutral-muted">Status ketersediaan membantu memilih sofa yang siap masuk daftar belanja.</p>
                        </div>
                        <div class="flex gap-3">
                            <Sparkles class="mt-1 h-5 w-5 shrink-0 text-primary-hover" />
                            <p class="text-sm leading-6 text-neutral-muted">Voucher dan koleksi unggulan hadir dekat dengan katalog utama.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-neutral-text py-14 text-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-primary">Alur belanja</p>
                        <h2 class="mt-2 text-3xl font-bold">Dari katalog sampai pengiriman.</h2>
                    </div>
                    <AppButton href="/catalog">Mulai Belanja</AppButton>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <article v-for="step in shoppingFlow" :key="step.label" class="rounded-md border border-white/20 p-5">
                        <component :is="step.icon" class="h-6 w-6 text-primary" />
                        <h3 class="mt-4 font-semibold text-white">{{ step.label }}</h3>
                        <p class="mt-2 text-sm leading-6 text-white/70">{{ step.description }}</p>
                    </article>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
