<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password" />

        <div class="mb-6">
            <p class="text-sm font-semibold text-info">SofaStore</p>
            <h1 class="mt-2 text-2xl font-bold text-neutral-text">Reset password</h1>
            <p class="mt-2 text-sm leading-6 text-neutral-muted">Masukkan email akun untuk menerima tautan reset password.</p>
        </div>

        <Alert v-if="status" class="mb-4" tone="success">
            {{ status }}
        </Alert>

        <form class="grid gap-4" @submit.prevent="submit">
            <FormInput id="email" v-model="form.email" type="email" label="Email" :error="form.errors.email" required />
            <AppButton type="submit" class="w-full" :loading="form.processing">Kirim Tautan Reset</AppButton>
        </form>

        <p class="mt-5 text-center text-sm text-neutral-muted">
            Ingat password?
            <Link :href="route('login')" class="font-semibold text-neutral-text hover:text-primary-hover">Masuk</Link>
        </p>
    </GuestLayout>
</template>
