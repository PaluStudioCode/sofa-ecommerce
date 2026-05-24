<script setup>
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';

const confirmingUserDeletion = ref(false);

const form = useForm({
    password: '',
});

function confirmUserDeletion() {
    confirmingUserDeletion.value = true;
}

function deleteUser() {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: closeModal,
        onFinish: () => form.reset(),
    });
}

function closeModal() {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
}
</script>

<template>
    <section>
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-danger">Hapus Akun</h2>
            <p class="mt-1 text-sm leading-6 text-neutral-muted">
                Setelah akun dihapus, akses dan data akun tidak dapat digunakan kembali.
            </p>
        </div>

        <AppButton type="button" variant="danger" @click="confirmUserDeletion">
            <Trash2 class="h-4 w-4" />
            Hapus Akun
        </AppButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-neutral-text">Konfirmasi hapus akun</h2>
                    <p class="mt-1 text-sm leading-6 text-neutral-muted">
                        Masukkan password untuk menghapus akun ini secara permanen.
                    </p>
                </div>

                <FormInput
                    id="delete_account_password"
                    v-model="form.password"
                    type="password"
                    label="Password"
                    :error="form.errors.password"
                    @keyup.enter="deleteUser"
                />

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <AppButton type="button" variant="secondary" @click="closeModal">Batal</AppButton>
                    <AppButton type="button" variant="danger" :loading="form.processing" @click="deleteUser">
                        <Trash2 class="h-4 w-4" />
                        Hapus Akun
                    </AppButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
