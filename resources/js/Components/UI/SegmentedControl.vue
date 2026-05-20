<script setup>
defineProps({
    options: { type: Array, default: () => [] },
    modelValue: { type: [String, Number, Boolean], default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="inline-flex rounded-md border border-neutral-border bg-neutral-light p-1">
        <button
            v-for="option in options"
            :key="String(option.value)"
            type="button"
            class="min-h-8 rounded px-3 text-sm font-semibold"
            :class="modelValue === option.value ? 'bg-white text-neutral-text shadow-sm' : 'text-neutral-muted hover:text-neutral-text'"
            @click="$emit('update:modelValue', option.value)"
        >
            {{ option.label }}
        </button>
    </div>
</template>
