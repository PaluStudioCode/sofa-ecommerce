<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { PlugZap, Settings, Store } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    storeContact: { type: Object, required: true },
    systemInfo: { type: Array, default: () => [] },
    integrations: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.storeContact.name || '',
    address: props.storeContact.address || '',
    email: props.storeContact.email || '',
    whatsapp: props.storeContact.whatsapp || '',
    hours: props.storeContact.hours || '',
});

function submit() {
    form.put(route('admin.system-settings.update'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Pengaturan Sistem" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Pengaturan Sistem">
        <div class="mb-5">
            <h2 class="text-xl font-semibold text-neutral-text">Pengaturan sistem</h2>
            <p class="mt-1 text-sm text-neutral-muted">Kelola identitas toko dan pantau konfigurasi aplikasi.</p>
        </div>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <form class="rounded-md border border-neutral-border bg-white p-5" @submit.prevent="submit">
                <div class="mb-5 flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-md bg-primary-soft text-neutral-text">
                        <Store class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-neutral-text">Kontak toko</h3>
                        <p class="text-sm text-neutral-muted">Data ini tampil di footer dan area publik.</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <FormInput id="store_name" v-model="form.name" label="Nama toko" :error="form.errors.name" required />
                    <FormInput id="store_email" v-model="form.email" type="email" label="Email toko" :error="form.errors.email" required />
                    <FormInput id="store_whatsapp" v-model="form.whatsapp" label="WhatsApp" :error="form.errors.whatsapp" required />
                    <FormInput id="store_hours" v-model="form.hours" label="Jam operasional" :error="form.errors.hours" required />
                    <label class="block md:col-span-2" for="store_address">
                        <span class="text-sm font-medium text-neutral-text">Alamat toko<span class="text-danger"> *</span></span>
                        <textarea id="store_address" v-model="form.address" rows="4" required class="mt-1 block min-h-28 w-full resize-y rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary" />
                        <p v-if="form.errors.address" class="mt-1 text-sm text-danger">{{ form.errors.address }}</p>
                    </label>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <AppButton type="submit" :loading="form.processing">
                        <Settings class="h-4 w-4" />
                        Simpan Pengaturan
                    </AppButton>
                </div>
            </form>

            <aside class="grid gap-5">
                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <Settings class="h-5 w-5 text-info" />
                        <h3 class="font-semibold text-neutral-text">Runtime</h3>
                    </div>
                    <dl class="grid gap-3 text-sm">
                        <div v-for="item in systemInfo" :key="item.label" class="flex justify-between gap-3 border-b border-neutral-border pb-3 last:border-b-0 last:pb-0">
                            <dt class="text-neutral-muted">{{ item.label }}</dt>
                            <dd class="break-all text-right font-semibold text-neutral-text">{{ item.value || '-' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-md border border-neutral-border bg-white p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <PlugZap class="h-5 w-5 text-info" />
                        <h3 class="font-semibold text-neutral-text">Integrasi</h3>
                    </div>
                    <div class="grid gap-3">
                        <div v-for="integration in integrations" :key="integration.label" class="rounded-md border border-neutral-border p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-neutral-text">{{ integration.label }}</p>
                                    <p class="mt-1 text-sm text-neutral-muted">{{ integration.description }}</p>
                                </div>
                                <StatusBadge :status="integration.status" />
                            </div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
