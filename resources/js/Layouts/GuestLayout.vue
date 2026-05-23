<script setup>
import PublicLayout from './PublicLayout.vue';
</script>

<template>
    <PublicLayout>
        <section class="grid min-h-[calc(100vh-4rem)] place-items-center bg-neutral-light px-4 py-10 sm:px-6">
            <div class="w-full max-w-md rounded-md border border-neutral-border bg-white p-6 shadow-xl">
                <slot />
            </div>
        </section>
    </PublicLayout>
</template>
