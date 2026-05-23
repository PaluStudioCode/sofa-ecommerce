<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Konfirmasi Password" />

        <div class="mb-6">
            <p class="text-sm font-semibold text-info">SofaStore</p>
            <h1 class="mt-2 text-2xl font-bold text-neutral-text">Konfirmasi password</h1>
            <p class="mt-2 text-sm leading-6 text-neutral-muted">Masukkan password untuk melanjutkan ke area akun.</p>
        </div>

        <form class="grid gap-4" @submit.prevent="submit">
            <FormInput id="password" v-model="form.password" type="password" label="Password" :error="form.errors.password" required />
            <AppButton type="submit" class="w-full" :loading="form.processing">Konfirmasi</AppButton>
        </form>
    </GuestLayout>
</template>
