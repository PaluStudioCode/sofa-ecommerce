<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, ShoppingCart } from '@lucide/vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import QuantityStepper from '@/Components/UI/QuantityStepper.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';

const props = defineProps({
    product: { type: Object, required: true },
});

const sofaFallback = 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80';
const selectedVariantId = ref(props.product.variants[0]?.id || null);
const selectedImageIndex = ref(0);
const quantity = ref(1);
const page = usePage();
const cartForm = useForm({
    product_id: props.product.id,
    product_variant_id: null,
    quantity: 1,
});

const selectedVariant = computed(() => props.product.variants.find((variant) => variant.id === selectedVariantId.value));
const allVariantImages = computed(() => props.product.variants.flatMap((variant) => variant.images || []));
const activeImages = computed(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.images || [];
    }

    return allVariantImages.value;
});
const selectedImage = computed(() => activeImages.value[selectedImageIndex.value]?.url || sofaFallback);
const selectedImageAlt = computed(() => activeImages.value[selectedImageIndex.value]?.alt_text || props.product.name);
const canAdd = computed(() => selectedVariant.value?.can_add_to_cart && quantity.value >= 1 && quantity.value <= selectedVariant.value.available_stock);

watch(selectedVariantId, () => {
    selectedImageIndex.value = 0;
});

watch(
    () => activeImages.value.length,
    () => {
        selectedImageIndex.value = 0;
    },
);

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
}

function addToCart() {
    if (!selectedVariant.value) return;

    cartForm.product_variant_id = selectedVariant.value.id;
    cartForm.quantity = quantity.value;
    cartForm.post(route('cart.store'), {
        preserveScroll: true,
    });
}

function selectImage(index) {
    selectedImageIndex.value = index;
}

function showPreviousImage() {
    if (!activeImages.value.length) return;

    selectedImageIndex.value = (selectedImageIndex.value - 1 + activeImages.value.length) % activeImages.value.length;
}

function showNextImage() {
    if (!activeImages.value.length) return;

    selectedImageIndex.value = (selectedImageIndex.value + 1) % activeImages.value.length;
}
</script>

<template>
    <Head :title="product.name" />

    <PublicLayout>
        <section class="bg-white py-8">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.95fr_1fr] lg:px-8">
                <div>
                    <div class="relative overflow-hidden rounded-md border border-neutral-border bg-neutral-light">
                        <img :src="selectedImage" :alt="selectedImageAlt" class="aspect-[4/3] w-full object-cover" />
                        <button
                            v-if="activeImages.length > 1"
                            type="button"
                            class="absolute left-3 top-1/2 inline-grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-white/70 bg-white/90 text-neutral-text shadow-sm hover:bg-white"
                            @click="showPreviousImage"
                        >
                            <ChevronLeft class="h-5 w-5" />
                            <span class="sr-only">Gambar sebelumnya</span>
                        </button>
                        <button
                            v-if="activeImages.length > 1"
                            type="button"
                            class="absolute right-3 top-1/2 inline-grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-white/70 bg-white/90 text-neutral-text shadow-sm hover:bg-white"
                            @click="showNextImage"
                        >
                            <ChevronRight class="h-5 w-5" />
                            <span class="sr-only">Gambar berikutnya</span>
                        </button>
                    </div>
                    <div v-if="activeImages.length > 1" class="mt-3 overflow-x-auto pb-2">
                        <div class="flex gap-2">
                            <button
                                v-for="(image, index) in activeImages"
                                :key="image.id"
                                type="button"
                                class="h-20 w-20 shrink-0 overflow-hidden rounded-md border bg-neutral-light"
                                :class="selectedImageIndex === index ? 'border-primary-hover ring-2 ring-primary-soft' : 'border-neutral-border'"
                                @click="selectImage(index)"
                            >
                                <img :src="image.url" :alt="image.alt_text || product.name" class="h-full w-full object-cover" />
                                <span class="sr-only">Pilih gambar {{ index + 1 }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-neutral-muted">{{ product.category?.name || 'Tanpa kategori' }}</p>
                    <h1 class="mt-2 text-3xl font-bold text-neutral-text">{{ product.name }}</h1>
                    <p class="mt-4 leading-7 text-neutral-muted">{{ product.description }}</p>

                    <div class="mt-6">
                        <h2 class="text-sm font-semibold text-neutral-text">Pilih varian</h2>
                        <div class="mt-3 grid gap-3">
                            <button
                                v-for="variant in product.variants"
                                :key="variant.id"
                                type="button"
                                class="rounded-md border p-4 text-left transition"
                                :class="selectedVariantId === variant.id ? 'border-primary-hover bg-primary-soft' : 'border-neutral-border bg-white hover:bg-neutral-light'"
                                @click="selectedVariantId = variant.id"
                            >
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="font-semibold text-neutral-text">{{ variant.variant_name || variant.sku || 'Varian standar' }}</p>
                                        <p class="mt-1 text-sm text-neutral-muted">
                                            {{ [variant.size, variant.material, variant.color].filter(Boolean).join(' / ') || 'Spesifikasi standar' }}
                                        </p>
                                        <p class="mt-1 text-sm text-neutral-muted">Stok tersedia: {{ variant.available_stock }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <StatusBadge :status="variant.can_add_to_cart ? 'aktif' : 'stok_habis'" :label="variant.can_add_to_cart ? 'Tersedia' : 'Tidak tersedia'" />
                                        <p class="text-sm font-semibold text-neutral-text">{{ formatRupiah(variant.price) }}</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <QuantityStepper v-model="quantity" :min="1" :max="selectedVariant?.available_stock || 1" />
                        <AppButton :disabled="!canAdd" :loading="cartForm.processing" @click="addToCart">
                            <ShoppingCart class="h-4 w-4" />
                            {{ page.props.auth.user ? 'Tambah ke Keranjang' : 'Login untuk Tambah' }}
                        </AppButton>
                    </div>
                    <div v-if="cartForm.errors.product_id || cartForm.errors.product_variant_id || cartForm.errors.quantity" class="mt-4">
                        <Alert tone="danger">
                            {{ cartForm.errors.product_id || cartForm.errors.product_variant_id || cartForm.errors.quantity }}
                        </Alert>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
