<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Login" />

        <div class="mb-6">
            <p class="text-sm font-semibold text-info">SofaStore</p>
            <h1 class="mt-2 text-2xl font-bold text-neutral-text">Masuk akun</h1>
            <p class="mt-2 text-sm leading-6 text-neutral-muted">Lanjutkan belanja, checkout, dan pantau pesanan sofa Anda.</p>
        </div>

        <Alert v-if="status" class="mb-4" tone="success">
            {{ status }}
        </Alert>

        <form class="grid gap-4" @submit.prevent="submit">
            <FormInput id="email" v-model="form.email" type="email" label="Email" :error="form.errors.email" required />
            <FormInput id="password" v-model="form.password" type="password" label="Password" :error="form.errors.password" required />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm font-medium text-neutral-text">
                    <input v-model="form.remember" type="checkbox" class="rounded border-neutral-border text-primary-hover focus:ring-primary" />
                    Ingat saya
                </label>

                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-semibold text-neutral-text hover:text-primary-hover">
                    Lupa password?
                </Link>
            </div>

            <AppButton type="submit" class="w-full" :loading="form.processing">Masuk</AppButton>
        </form>

        <p class="mt-5 text-center text-sm text-neutral-muted">
            Belum punya akun?
            <Link :href="route('register')" class="font-semibold text-neutral-text hover:text-primary-hover">Daftar</Link>
        </p>
    </GuestLayout>
</template>
