<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import { GripVertical, Trash2 } from '@lucide/vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    product: { type: Object, required: true },
    variants: { type: Array, default: () => [] },
    fixedVariantId: { type: [Number, String], default: '' },
});

const emit = defineEmits(['close']);
const fileInput = ref(null);
const previews = ref([]);
const draggedPreviewIndex = ref(null);
const previewDropIndex = ref(null);

const form = useForm({
    product_id: props.product.id,
    product_variant_id: '',
    images: [],
    alt_text: '',
});

const hasFixedVariant = computed(() => props.fixedVariantId !== '');
const fixedVariant = computed(() => props.variants.find((variant) => Number(variant.id) === Number(props.fixedVariantId)));
const fixedVariantLabel = computed(() => fixedVariant.value?.variant_name || fixedVariant.value?.sku || (fixedVariant.value ? `Varian ${fixedVariant.value.id}` : 'Varian'));
const variantOptions = computed(() => [
    { value: '', label: 'Pilih varian' },
    ...props.variants.map((variant) => ({
        value: variant.id,
        label: variant.variant_name || variant.sku || `Varian ${variant.id}`,
    })),
]);
const imageError = computed(() => form.errors.images || form.errors.image || Object.entries(form.errors).find(([key]) => key.startsWith('images.'))?.[1]);

function revokePreviews() {
    previews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
    previews.value = [];
}

function clearSelectedImages() {
    revokePreviews();
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    form.images = [];
}

function fillForm() {
    const productVariantId = props.fixedVariantId || '';

    clearSelectedImages();
    form.defaults({
        product_id: props.product.id,
        product_variant_id: productVariantId,
        images: [],
        alt_text: '',
    });
    form.reset();
    form.product_variant_id = productVariantId;
    form.clearErrors();
}

function selectImages(event) {
    revokePreviews();

    const files = Array.from(event.target.files || []);
    form.images = files;
    previews.value = files.map((file) => ({
        name: file.name,
        size: file.size,
        url: URL.createObjectURL(file),
    }));
}

function removeImage(index) {
    URL.revokeObjectURL(previews.value[index].url);
    previews.value.splice(index, 1);
    form.images.splice(index, 1);

    if (fileInput.value && form.images.length === 0) {
        fileInput.value.value = '';
    }
}

function movePreview(fromIndex, toIndex) {
    if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= previews.value.length || toIndex >= previews.value.length) {
        return;
    }

    const [preview] = previews.value.splice(fromIndex, 1);
    previews.value.splice(toIndex, 0, preview);

    const [file] = form.images.splice(fromIndex, 1);
    form.images.splice(toIndex, 0, file);
}

function startPreviewDrag(event, index) {
    draggedPreviewIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(index));

    const dragCard = event.currentTarget.closest('[data-preview-card]');
    if (dragCard) {
        setPreviewDragImage(event, dragCard);
    }
}

function setPreviewDragImage(event, element) {
    const rect = element.getBoundingClientRect();
    const ghost = element.cloneNode(true);

    ghost.style.position = 'fixed';
    ghost.style.top = '-1000px';
    ghost.style.left = '-1000px';
    ghost.style.width = `${rect.width}px`;
    ghost.style.opacity = '1';
    ghost.style.pointerEvents = 'none';
    ghost.style.boxShadow = '0 12px 30px rgba(15, 23, 42, 0.18)';

    document.body.appendChild(ghost);
    event.dataTransfer.setDragImage(ghost, 20, 20);
    setTimeout(() => ghost.remove(), 0);
}

function enterPreviewDrop(index) {
    previewDropIndex.value = index;
}

function dropPreview(event, index) {
    const fallbackIndex = Number(event.dataTransfer.getData('text/plain'));
    const fromIndex = draggedPreviewIndex.value ?? fallbackIndex;

    if (Number.isInteger(fromIndex)) {
        movePreview(fromIndex, index);
    }

    clearPreviewDrag();
}

function clearPreviewDrag() {
    draggedPreviewIndex.value = null;
    previewDropIndex.value = null;
}

function formatFileSize(size) {
    return `${Math.max(1, Math.round(size / 1024))} KB`;
}

watch(
    () => [props.show, props.product.id, props.fixedVariantId],
    () => {
        if (props.show) {
            fillForm();
        }
    },
    { immediate: true },
);

function close() {
    if (!form.processing) {
        clearSelectedImages();
        emit('close');
    }
}

function submit() {
    form.product_id = props.product.id;
    form.post(route('admin.product-images.store'), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            clearSelectedImages();
            emit('close');
        },
    });
}

onBeforeUnmount(revokePreviews);
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <form class="p-6" @submit.prevent="submit">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-neutral-text">Upload Gambar Produk</h2>
                <p class="mt-1 text-sm text-neutral-muted">{{ product.name }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div v-if="hasFixedVariant">
                    <p class="text-sm font-medium text-neutral-text">Varian</p>
                    <div class="mt-1 flex min-h-10 items-center rounded-md border border-neutral-border bg-neutral-light px-3 text-sm font-semibold text-neutral-text">
                        {{ fixedVariantLabel }}
                    </div>
                    <p v-if="form.errors.product_variant_id" class="mt-1 text-sm text-danger">{{ form.errors.product_variant_id }}</p>
                </div>
                <FormSelect v-else id="product_image_variant_id" v-model="form.product_variant_id" label="Varian" :options="variantOptions" :error="form.errors.product_variant_id" required />
                <FormInput
                    id="product_image_alt_text"
                    v-model="form.alt_text"
                    label="Deskripsi Gambar (Opsional)"
                    placeholder="Contoh: Sofa Luna warna abu-abu tampak depan"
                    :error="form.errors.alt_text"
                />
            </div>

            <label v-show="!previews.length" class="mt-4 block" for="product_image_file">
                <span class="text-sm font-medium text-neutral-text">File Gambar<span class="text-danger"> *</span></span>
                <input ref="fileInput" id="product_image_file" type="file" accept="image/png,image/jpeg,image/webp" multiple class="mt-1 block w-full rounded-md border border-neutral-border text-sm text-neutral-text file:mr-4 file:min-h-10 file:border-0 file:bg-neutral-light file:px-4 file:text-sm file:font-semibold" @change="selectImages" />
                <p v-if="imageError" class="mt-1 text-sm text-danger">{{ imageError }}</p>
            </label>

            <div v-if="previews.length" class="mt-4">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-neutral-text">{{ previews.length }} gambar dipilih</p>
                    <button type="button" class="rounded-md border border-neutral-border px-3 py-1.5 text-xs font-semibold text-neutral-text hover:bg-neutral-light" @click="fileInput?.click()">
                        Ganti gambar
                    </button>
                </div>

                <div class="max-h-72 overflow-y-auto rounded-md border border-neutral-border bg-white p-2">
                    <div class="grid gap-2">
                        <article
                            v-for="(preview, index) in previews"
                            :key="preview.url"
                            class="flex items-center gap-3 rounded-md border p-2 transition"
                            :class="previewDropIndex === index && draggedPreviewIndex !== index ? 'border-primary-hover bg-primary-soft' : 'border-neutral-border bg-white'"
                            data-preview-card
                            @dragover.prevent
                            @dragenter.prevent="enterPreviewDrop(index)"
                            @drop.prevent="dropPreview($event, index)"
                        >
                            <span
                                draggable="true"
                                class="inline-grid h-8 w-6 shrink-0 cursor-grab place-items-center rounded text-neutral-muted active:cursor-grabbing"
                                title="Geser"
                                @dragstart="startPreviewDrag($event, index)"
                                @dragend="clearPreviewDrag"
                            >
                                <GripVertical class="h-4 w-4" />
                                <span class="sr-only">Geser</span>
                            </span>
                            <img :src="preview.url" :alt="preview.name" class="h-14 w-16 shrink-0 rounded object-cover" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-neutral-text">{{ preview.name }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-neutral-muted">
                                    <span>Urutan {{ index + 1 }}</span>
                                    <span>{{ formatFileSize(preview.size) }}</span>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" class="inline-grid h-8 w-8 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="removeImage(index)">
                                    <Trash2 class="h-4 w-4" />
                                    <span class="sr-only">Hapus</span>
                                </button>
                            </div>
                        </article>
                    </div>
                </div>
                <p v-if="imageError" class="mt-1 text-sm text-danger">{{ imageError }}</p>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <AppButton type="button" variant="secondary" @click="close">Batal</AppButton>
                <AppButton type="submit" :loading="form.processing">Upload</AppButton>
            </div>
        </form>
    </Modal>
</template>
