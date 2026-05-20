<script setup>
defineProps({
    show: { type: Boolean, default: false },
    tone: { type: String, default: 'success' },
});
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="show"
            class="fixed bottom-4 right-4 z-50 rounded-md border bg-white px-4 py-3 text-sm shadow-lg"
            :class="tone === 'danger' ? 'border-red-200 text-danger' : 'border-green-200 text-success'"
            role="status"
        >
            <slot />
        </div>
    </transition>
</template>
