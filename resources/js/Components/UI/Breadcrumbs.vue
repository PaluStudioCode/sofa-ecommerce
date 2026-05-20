<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: { type: Array, default: () => [] },
});
</script>

<template>
    <nav v-if="items.length" aria-label="Breadcrumb" class="text-sm text-neutral-muted">
        <ol class="flex flex-wrap items-center gap-2">
            <li v-for="(item, index) in items" :key="`${item.label}-${index}`" class="flex items-center gap-2">
                <span v-if="index > 0">/</span>
                <Link v-if="item.href && index < items.length - 1" :href="item.href" class="hover:text-neutral-text">
                    {{ item.label }}
                </Link>
                <span v-else class="font-medium text-neutral-text">{{ item.label }}</span>
            </li>
        </ol>
    </nav>
</template>
