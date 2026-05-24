<script setup>
import Alert from '@/Components/UI/Alert.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';

defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: '' },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name || '',
    email: user.email || '',
    phone: user.phone || '',
});

function submit() {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <section>
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-neutral-text">Informasi Profil</h2>
            <p class="mt-1 text-sm text-neutral-muted">Perbarui nama, email, dan nomor telepon akun.</p>
        </div>

        <form class="grid gap-4" @submit.prevent="submit">
            <FormInput id="profile_name" v-model="form.name" label="Nama" :error="form.errors.name" required />
            <FormInput id="profile_email" v-model="form.email" type="email" label="Email" :error="form.errors.email" required />
            <FormInput id="profile_phone" v-model="form.phone" label="Telepon" placeholder="Contoh: 081234567890" :error="form.errors.phone" />

            <Alert v-if="mustVerifyEmail && user.email_verified_at === null" tone="warning">
                Email belum terverifikasi.
                <Link :href="route('verification.send')" method="post" as="button" class="font-semibold underline">
                    Kirim ulang email verifikasi
                </Link>
                <span v-if="status === 'verification-link-sent'"> Link verifikasi baru sudah dikirim.</span>
            </Alert>

            <div class="flex flex-wrap items-center gap-3">
                <AppButton type="submit" :loading="form.processing">
                    <Save class="h-4 w-4" />
                    Simpan Profil
                </AppButton>
                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-success">Profil tersimpan.</p>
            </div>
        </form>
    </section>
</template>
