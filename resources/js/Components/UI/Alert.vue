<script setup>
defineProps({
    tone: { type: String, default: 'info' },
});
</script>

<template>
    <div
        class="rounded-md border p-4 text-sm"
        :class="{
            'border-blue-200 bg-blue-50 text-info': tone === 'info',
            'border-green-200 bg-green-50 text-success': tone === 'success',
            'border-red-200 bg-red-50 text-danger': tone === 'danger',
            'border-yellow-200 bg-primary-soft text-neutral-text': tone === 'warning',
        }"
        role="alert"
    >
        <slot />
    </div>
</template>
