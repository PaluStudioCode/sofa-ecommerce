<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <div class="mb-6">
            <p class="text-sm font-semibold text-info">SofaStore</p>
            <h1 class="mt-2 text-2xl font-bold text-neutral-text">Password baru</h1>
            <p class="mt-2 text-sm leading-6 text-neutral-muted">Buat password baru untuk mengakses akun Anda.</p>
        </div>

        <form class="grid gap-4" @submit.prevent="submit">
            <FormInput id="email" v-model="form.email" type="email" label="Email" :error="form.errors.email" required />
            <FormInput id="password" v-model="form.password" type="password" label="Password" :error="form.errors.password" required />
            <FormInput id="password_confirmation" v-model="form.password_confirmation" type="password" label="Konfirmasi Password" :error="form.errors.password_confirmation" required />

            <AppButton type="submit" class="w-full" :loading="form.processing">Simpan Password</AppButton>
        </form>
    </GuestLayout>
</template>
