<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Edit, Plus, Trash2 } from '@lucide/vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    roles: { type: Array, default: () => [] },
    internalRoles: { type: Array, default: () => [] },
});

const page = usePage();
const editingId = ref(null);
const filterForm = useForm({
    keyword: props.filters.keyword || '',
    role: props.filters.role || '',
});
const form = useForm({
    name: '',
    email: '',
    phone: '',
    role: 'admin',
    password: '',
    password_confirmation: '',
});

const columns = [
    { key: 'name', label: 'Nama' },
    { key: 'email', label: 'Email' },
    { key: 'phone', label: 'Telepon' },
    { key: 'role', label: 'Role' },
    { key: 'orders_count', label: 'Order' },
];

function submitFilter() {
    filterForm.get(route('admin.users.index'), { preserveState: true, replace: true });
}

function resetForm() {
    editingId.value = null;
    form.transform((data) => data);
    form.reset();
    form.role = 'admin';
    form.clearErrors();
}

function edit(user) {
    editingId.value = user.id;
    Object.assign(form, {
        name: user.name,
        email: user.email,
        phone: user.phone || '',
        role: user.role,
        password: '',
        password_confirmation: '',
    });
}

function submit() {
    if (editingId.value) {
        form.transform((data) => ({
            name: data.name,
            email: data.email,
            phone: data.phone,
            role: data.role,
        })).put(route('admin.users.update', editingId.value), {
            onSuccess: resetForm,
        });
        return;
    }

    form.post(route('admin.users.store'), { onSuccess: resetForm });
}

function deactivate(user) {
    if (window.confirm(`Nonaktifkan pengguna ${user.name}?`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
}
</script>

<template>
    <Head title="Pengguna" />

    <AuthenticatedLayout :navigation-groups="navigationGroups" :breadcrumbs="breadcrumbs" title="Pengguna">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-neutral-text">Manajemen pengguna</h2>
            <p class="mt-1 text-sm text-neutral-muted">Admin dapat membuat akun internal, mengubah role, dan menonaktifkan akun tanpa melihat password lama.</p>
            <p v-if="page.props.flash?.success" class="mt-1 text-sm text-success">{{ page.props.flash.success }}</p>
            <p v-if="page.props.flash?.error" class="mt-1 text-sm text-danger">{{ page.props.flash.error }}</p>
        </div>

        <form class="mb-5 rounded-md border border-neutral-border bg-white p-5" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-3">
                <FormInput id="user_name" v-model="form.name" label="Nama" :error="form.errors.name" required />
                <FormInput id="user_email" v-model="form.email" type="email" label="Email" :error="form.errors.email" required />
                <FormInput id="user_phone" v-model="form.phone" label="Telepon" :error="form.errors.phone" />
                <FormSelect id="user_role" v-model="form.role" label="Role" :options="editingId ? roles.filter((role) => role.value !== '') : internalRoles" :error="form.errors.role" required />
                <FormInput v-if="!editingId" id="user_password" v-model="form.password" type="password" label="Password" :error="form.errors.password" required />
                <FormInput v-if="!editingId" id="user_password_confirmation" v-model="form.password_confirmation" type="password" label="Konfirmasi Password" :error="form.errors.password_confirmation" required />
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <AppButton type="submit" :loading="form.processing">
                    <Plus class="h-4 w-4" />
                    {{ editingId ? 'Update Pengguna' : 'Tambah Internal' }}
                </AppButton>
                <AppButton v-if="editingId" type="button" variant="secondary" @click="resetForm">Batal</AppButton>
            </div>
        </form>

        <form class="mb-4 grid gap-3 rounded-md border border-neutral-border bg-white p-4 md:grid-cols-[1fr_220px_auto]" @submit.prevent="submitFilter">
            <FormInput id="keyword" v-model="filterForm.keyword" label="Keyword" placeholder="Cari nama, email, atau telepon" />
            <FormSelect id="role" v-model="filterForm.role" label="Role" :options="roles" />
            <div class="flex items-end">
                <AppButton type="submit">Filter</AppButton>
            </div>
        </form>

        <DataTable :columns="columns" :rows="users.data">
            <template #cell-name="{ row }">
                <div>
                    <p class="font-semibold text-neutral-text">{{ row.name }}</p>
                    <p class="text-xs text-neutral-muted">ID {{ row.id }}</p>
                </div>
            </template>
            <template #cell-role="{ value }"><StatusBadge :status="value" :label="value" /></template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-neutral-border hover:bg-neutral-light" @click="edit(row)">
                        <Edit class="h-4 w-4" />
                        <span class="sr-only">Edit</span>
                    </button>
                    <button type="button" class="inline-grid h-9 w-9 place-items-center rounded-md border border-red-200 text-danger hover:bg-red-50" @click="deactivate(row)">
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Nonaktifkan</span>
                    </button>
                </div>
            </template>
            <template #empty>
                <EmptyState title="Belum ada pengguna" />
            </template>
        </DataTable>

        <Pagination class="mt-4" :links="users.links" />
    </AuthenticatedLayout>
</template>
