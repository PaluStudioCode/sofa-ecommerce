<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: { type: Array, default: () => [] },
});
</script>

<template>
    <nav v-if="links.length" class="flex flex-wrap items-center gap-2" aria-label="Pagination">
        <Link
            v-for="link in links"
            :key="link.label"
            :href="link.url || '#'"
            class="rounded-md border px-3 py-2 text-sm"
            :class="[
                link.active ? 'border-primary bg-primary text-neutral-text' : 'border-neutral-border bg-white text-neutral-muted',
                !link.url ? 'pointer-events-none opacity-50' : 'hover:bg-neutral-light',
            ]"
            v-html="link.label"
        />
    </nav>
</template>
