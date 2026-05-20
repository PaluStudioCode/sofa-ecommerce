<script setup>
defineProps({
    tabs: { type: Array, default: () => [] },
    modelValue: { type: String, default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="border-b border-neutral-border">
        <div class="flex gap-4 overflow-x-auto">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                class="min-h-10 border-b-2 px-1 text-sm font-semibold"
                :class="modelValue === tab.value ? 'border-primary-hover text-neutral-text' : 'border-transparent text-neutral-muted hover:text-neutral-text'"
                @click="$emit('update:modelValue', tab.value)"
            >
                {{ tab.label }}
            </button>
        </div>
    </div>
</template>
