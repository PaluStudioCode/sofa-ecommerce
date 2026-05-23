<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="mb-6">
            <p class="text-sm font-semibold text-info">SofaStore</p>
            <h1 class="mt-2 text-2xl font-bold text-neutral-text">Buat akun</h1>
            <p class="mt-2 text-sm leading-6 text-neutral-muted">Siapkan akun untuk checkout dan riwayat pesanan.</p>
        </div>

        <form class="grid gap-4" @submit.prevent="submit">
            <FormInput id="name" v-model="form.name" label="Nama" :error="form.errors.name" required />
            <FormInput id="email" v-model="form.email" type="email" label="Email" :error="form.errors.email" required />
            <FormInput id="password" v-model="form.password" type="password" label="Password" :error="form.errors.password" required />
            <FormInput id="password_confirmation" v-model="form.password_confirmation" type="password" label="Konfirmasi Password" :error="form.errors.password_confirmation" required />

            <AppButton type="submit" class="w-full" :loading="form.processing">Daftar</AppButton>
        </form>

        <p class="mt-5 text-center text-sm text-neutral-muted">
            Sudah punya akun?
            <Link :href="route('login')" class="font-semibold text-neutral-text hover:text-primary-hover">Masuk</Link>
        </p>
    </GuestLayout>
</template>
