<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: '' },
});

const tone = computed(() => {
    if (['success', 'barang_diterima', 'sent', 'aktif'].includes(props.status)) return 'success';
    if (['failed', 'nonaktif'].includes(props.status)) return 'danger';
    if (['pending', 'menunggu_pembayaran', 'perlu_konfigurasi'].includes(props.status)) return 'warning';
    if (['expired', 'kedaluwarsa', 'kuota_habis', 'cancelled'].includes(props.status)) return 'neutral';
    return 'info';
});

const classes = computed(() => ({
    success: 'border-green-200 bg-green-50 text-success',
    danger: 'border-red-200 bg-red-50 text-danger',
    warning: 'border-yellow-200 bg-primary-soft text-neutral-text',
    neutral: 'border-neutral-border bg-neutral-light text-neutral-muted',
    info: 'border-blue-200 bg-blue-50 text-info',
}[tone.value]));
</script>

<template>
    <span class="inline-flex w-fit shrink-0 justify-self-start whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold" :class="classes">
        {{ label || status.replaceAll('_', ' ') }}
    </span>
</template>
