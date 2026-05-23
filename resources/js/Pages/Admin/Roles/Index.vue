<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Role" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Role">
        <div class="mb-5">
            <h2 class="text-xl font-semibold text-neutral-text">Role dan permission</h2>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <section v-for="role in roles" :key="role.role" class="rounded-md border border-neutral-border bg-white p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold capitalize text-neutral-text">{{ role.role }}</h3>
                    <StatusBadge :status="role.role === 'customer' ? 'pending' : 'aktif'" :label="role.permissions.length ? `${role.permissions.length} izin` : 'Publik'" />
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <StatusBadge
                        v-for="permission in role.permissions"
                        :key="permission"
                        status="aktif"
                        :label="permission"
                    />
                    <p v-if="role.permissions.length === 0" class="text-sm text-neutral-muted">Tidak memiliki akses dashboard internal.</p>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
