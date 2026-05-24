<script setup>
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import { useForm } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function updatePassword() {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
            }

            if (form.errors.current_password) {
                form.reset('current_password');
            }
        },
    });
}
</script>

<template>
    <section>
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-neutral-text">Password</h2>
            <p class="mt-1 text-sm text-neutral-muted">Gunakan password yang kuat untuk menjaga keamanan akun.</p>
        </div>

        <form class="grid gap-4" @submit.prevent="updatePassword">
            <FormInput id="current_password" v-model="form.current_password" type="password" label="Password Saat Ini" :error="form.errors.current_password" />
            <FormInput id="password" v-model="form.password" type="password" label="Password Baru" :error="form.errors.password" />
            <FormInput id="password_confirmation" v-model="form.password_confirmation" type="password" label="Konfirmasi Password Baru" :error="form.errors.password_confirmation" />

            <div class="flex flex-wrap items-center gap-3">
                <AppButton type="submit" :loading="form.processing">
                    <KeyRound class="h-4 w-4" />
                    Simpan Password
                </AppButton>
                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-success">Password tersimpan.</p>
            </div>
        </form>
    </section>
</template>
