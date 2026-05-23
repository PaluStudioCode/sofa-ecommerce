<script setup>
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle, CircleHelp, Info, X } from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import {
    dismissToast,
    showToast,
    useConfirm,
    useToast,
} from '@/Composables/useFeedback';

const page = usePage();
const { toasts } = useToast();
const { confirmDialog, cancelConfirm, acceptConfirm } = useConfirm();

const toneIcon = {
    success: CheckCircle,
    danger: AlertTriangle,
    warning: AlertTriangle,
    info: Info,
};

const toastToneClass = {
    success: 'border-green-200 bg-green-50 text-success',
    danger: 'border-red-200 bg-red-50 text-danger',
    warning: 'border-yellow-200 bg-yellow-50 text-yellow-700',
    info: 'border-blue-200 bg-blue-50 text-info',
};

const confirmToneClass = computed(() => ({
    danger: 'bg-red-50 text-danger',
    warning: 'bg-yellow-50 text-yellow-700',
    info: 'bg-blue-50 text-info',
    success: 'bg-green-50 text-success',
}[confirmDialog.tone] || 'bg-neutral-light text-neutral-muted'));

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            showToast({ tone: 'success', title: 'Berhasil', message });
        }
    },
    { immediate: true },
);

watch(
    () => page.props.flash?.error,
    (message) => {
        if (message) {
            showToast({ tone: 'danger', title: 'Terjadi kesalahan', message });
        }
    },
    { immediate: true },
);
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed right-4 top-4 z-[70] grid w-[calc(100vw-2rem)] max-w-sm gap-3 sm:right-6 sm:top-6">
            <TransitionGroup
                enter-active-class="transition ease-out duration-200"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto rounded-md border px-4 py-3 shadow-lg"
                    :class="toastToneClass[toast.tone] || toastToneClass.info"
                    :role="toast.tone === 'danger' ? 'alert' : 'status'"
                >
                    <div class="flex gap-3">
                        <component :is="toneIcon[toast.tone] || Info" class="mt-0.5 h-5 w-5 shrink-0" />
                        <div class="min-w-0 flex-1">
                            <p v-if="toast.title" class="text-sm font-semibold">{{ toast.title }}</p>
                            <p class="text-sm">{{ toast.message }}</p>
                        </div>
                        <button
                            type="button"
                            class="grid h-7 w-7 shrink-0 place-items-center rounded-md hover:bg-white/70"
                            @click="dismissToast(toast.id)"
                        >
                            <X class="h-4 w-4" />
                            <span class="sr-only">Tutup notifikasi</span>
                        </button>
                    </div>
                </div>
            </TransitionGroup>
        </div>

        <Modal :show="confirmDialog.open" max-width="md" @close="cancelConfirm">
            <div class="p-6">
                <div class="flex gap-4">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full" :class="confirmToneClass">
                        <component :is="confirmDialog.tone === 'info' ? CircleHelp : AlertTriangle" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold text-neutral-text">{{ confirmDialog.title }}</h2>
                        <p class="mt-2 text-sm leading-6 text-neutral-muted">{{ confirmDialog.message }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <AppButton type="button" variant="secondary" @click="cancelConfirm">
                        {{ confirmDialog.cancelText }}
                    </AppButton>
                    <AppButton type="button" :variant="confirmDialog.tone === 'danger' ? 'danger' : 'primary'" @click="acceptConfirm">
                        {{ confirmDialog.confirmText }}
                    </AppButton>
                </div>
            </div>
        </Modal>
    </Teleport>
</template>
