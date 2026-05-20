<script setup>
defineProps({
    title: { type: String, required: true },
    message: { type: String, default: '' },
});
</script>

<template>
    <div class="rounded-md border border-dashed border-neutral-border bg-white p-8 text-center">
        <p class="text-base font-semibold text-neutral-text">{{ title }}</p>
        <p v-if="message" class="mt-1 text-sm text-neutral-muted">{{ message }}</p>
        <div v-if="$slots.actions" class="mt-4 flex justify-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
