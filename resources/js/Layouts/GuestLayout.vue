<script setup>
import PublicLayout from './PublicLayout.vue';
</script>

<template>
    <PublicLayout>
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-md items-center px-4 py-10">
            <div class="w-full rounded-md border border-neutral-border bg-white p-6 shadow-sm">
                <slot />
            </div>
        </div>
    </PublicLayout>
</template>
