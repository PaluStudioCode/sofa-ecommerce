<script setup>
defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    meta: { type: String, default: '' },
});
</script>

<template>
    <section class="rounded-md border border-neutral-border bg-white p-4 shadow-sm">
        <p class="text-sm font-medium text-neutral-muted">{{ title }}</p>
        <p class="mt-2 text-2xl font-semibold text-neutral-text">{{ value }}</p>
        <p v-if="meta" class="mt-1 text-sm text-neutral-muted">{{ meta }}</p>
    </section>
</template>
