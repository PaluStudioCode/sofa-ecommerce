<script setup>
import { computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { SlidersHorizontal } from '@lucide/vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
});

const sofaFallback = 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80';

const form = useForm({
    keyword: props.filters.keyword || '',
    category: props.filters.category || '',
    min_price: props.filters.min_price || '',
    max_price: props.filters.max_price || '',
});

const categoryOptions = computed(() => [
    { value: '', label: 'Semua kategori' },
    ...props.categories.map((category) => ({ value: category.id, label: category.name })),
]);

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

function submit() {
    form.get(route('catalog.index'), {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    router.get(route('catalog.index'));
}
</script>

<template>
    <Head title="Katalog Sofa" />

    <PublicLayout>
        <section class="border-b border-neutral-border bg-white">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-neutral-text">Katalog Sofa</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-muted">Temukan sofa aktif dengan varian harga dan stok terbaru dari toko.</p>
                    </div>
                </div>

                <form class="mt-6 grid gap-3 rounded-md border border-neutral-border bg-neutral-light p-4 md:grid-cols-[1fr_220px_160px_160px_auto]" @submit.prevent="submit">
                    <FormInput id="keyword" v-model="form.keyword" label="Keyword" placeholder="Cari sofa" />
                    <FormSelect id="category" v-model="form.category" label="Kategori" :options="categoryOptions" />
                    <FormInput id="min_price" v-model="form.min_price" type="number" label="Harga Min" />
                    <FormInput id="max_price" v-model="form.max_price" type="number" label="Harga Max" />
                    <div class="flex items-end gap-2">
                        <AppButton type="submit">
                            <SlidersHorizontal class="h-4 w-4" />
                            Filter
                        </AppButton>
                        <AppButton type="button" variant="secondary" @click="resetFilters">Reset</AppButton>
                    </div>
                </form>
            </div>
        </section>

        <section class="bg-neutral-light py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="products.data.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link v-for="product in products.data" :key="product.id" :href="route('products.show', product.slug)" class="overflow-hidden rounded-md border border-neutral-border bg-white transition hover:border-primary-hover">
                        <img :src="product.image_url || sofaFallback" :alt="product.name" class="aspect-[4/3] w-full object-cover" />
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="font-semibold text-neutral-text">{{ product.name }}</h2>
                                    <p class="text-sm text-neutral-muted">{{ product.category || 'Tanpa kategori' }}</p>
                                </div>
                                <StatusBadge :status="product.available ? 'aktif' : 'stok_habis'" :label="product.available ? 'Tersedia' : 'Stok habis'" />
                            </div>
                            <p class="mt-3 text-sm font-semibold text-neutral-text">{{ priceRange(product) }}</p>
                        </div>
                    </Link>
                </div>

                <EmptyState v-else title="Produk tidak ditemukan" message="Coba ubah keyword, kategori, atau rentang harga." />

                <Pagination class="mt-6" :links="products.links" />
            </div>
        </section>
    </PublicLayout>
</template>
