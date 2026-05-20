<script setup>
import { Search } from '@lucide/vue';

defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Cari' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="relative block">
        <span class="sr-only">Cari</span>
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-muted" />
        <input
            :value="modelValue"
            :placeholder="placeholder"
            class="block min-h-10 w-full rounded-md border-neutral-border pl-9 text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary"
            type="search"
            @input="$emit('update:modelValue', $event.target.value)"
        />
    </label>
</template>
