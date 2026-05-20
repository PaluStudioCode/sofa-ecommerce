<script setup>
import DashboardLayout from './DashboardLayout.vue';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    title: { type: String, default: 'Dashboard' },
});
</script>

<template>
    <DashboardLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" :title="title">
        <slot />
    </DashboardLayout>
</template>
