<script setup>
defineProps({
    steps: { type: Array, default: () => [] },
});
</script>

<template>
    <ol class="space-y-4">
        <li v-for="step in steps" :key="step.label" class="flex gap-3">
            <span
                class="mt-1 h-3 w-3 shrink-0 rounded-full border"
                :class="step.done ? 'border-success bg-success' : 'border-neutral-border bg-white'"
            />
            <div>
                <p class="text-sm font-semibold text-neutral-text">{{ step.label }}</p>
                <p v-if="step.description" class="text-sm text-neutral-muted">{{ step.description }}</p>
            </div>
        </li>
    </ol>
</template>
