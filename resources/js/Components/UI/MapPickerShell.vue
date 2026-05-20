<script setup>
import { MapPinned } from '@lucide/vue';

defineProps({
    title: { type: String, default: 'Peta' },
    address: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
});
</script>

<template>
    <section class="overflow-hidden rounded-md border border-neutral-border bg-white">
        <div class="flex items-center justify-between border-b border-neutral-border px-4 py-3">
            <div class="flex items-center gap-2 text-sm font-semibold text-neutral-text">
                <MapPinned class="h-4 w-4 text-info" />
                <span>{{ title }}</span>
            </div>
            <slot name="actions" />
        </div>
        <div class="grid min-h-64 place-items-center bg-neutral-light p-4">
            <slot>
                <div class="text-center text-sm text-neutral-muted">
                    <p v-if="loading">Memuat peta...</p>
                    <p v-else-if="error" class="text-danger">{{ error }}</p>
                    <p v-else>{{ address || 'Pilih lokasi' }}</p>
                </div>
            </slot>
        </div>
    </section>
</template>
