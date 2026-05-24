<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: '' },
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
const layoutComponent = computed(() => isAdmin.value ? AuthenticatedLayout : PublicLayout);
const layoutProps = computed(() => isAdmin.value ? {
    navigationGroups: props.navigationGroups,
    breadcrumbs: props.breadcrumbs,
    title: 'Profil Saya',
} : {});
</script>

<template>
    <Head title="Profil Saya" />

    <component :is="layoutComponent" v-bind="layoutProps">
        <section class="bg-neutral-light py-6 sm:py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold capitalize text-neutral-muted">{{ page.props.auth.user.role }}</p>
                        <h1 class="text-2xl font-bold text-neutral-text">Profil Saya</h1>
                    </div>
                    <p class="text-sm text-neutral-muted">{{ page.props.auth.user.email }}</p>
                </div>

                <div class="grid items-start gap-5 lg:grid-cols-2">
                    <div class="rounded-md border border-neutral-border bg-white p-5">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </div>

                    <div class="rounded-md border border-neutral-border bg-white p-5">
                        <UpdatePasswordForm />
                    </div>
                </div>

                <div class="mt-5 rounded-md border border-red-200 bg-white p-5">
                    <DeleteUserForm />
                </div>
            </div>
        </section>
    </component>
</template>
