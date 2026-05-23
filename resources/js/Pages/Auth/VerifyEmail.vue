<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: { type: String },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <div class="mb-6">
            <p class="text-sm font-semibold text-info">SofaStore</p>
            <h1 class="mt-2 text-2xl font-bold text-neutral-text">Verifikasi email</h1>
            <p class="mt-2 text-sm leading-6 text-neutral-muted">Cek email Anda untuk menyelesaikan aktivasi akun.</p>
        </div>

        <Alert v-if="verificationLinkSent" class="mb-4" tone="success">
            Tautan verifikasi baru sudah dikirim ke email yang terdaftar.
        </Alert>

        <form @submit.prevent="submit">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <AppButton type="submit" :loading="form.processing">Kirim Ulang Email</AppButton>

                <Link :href="route('logout')" method="post" as="button" class="rounded-md px-3 py-2 text-sm font-semibold text-neutral-muted hover:bg-neutral-light hover:text-neutral-text">
                    Logout
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
