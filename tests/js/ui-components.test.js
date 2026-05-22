import { mount } from '@vue/test-utils';
import { beforeAll, describe, expect, it, vi } from 'vitest';

import Alert from '@/Components/UI/Alert.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import MapPickerShell from '@/Components/UI/MapPickerShell.vue';
import QuantityStepper from '@/Components/UI/QuantityStepper.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import SummaryCard from '@/Components/UI/SummaryCard.vue';
import Timeline from '@/Components/UI/Timeline.vue';
import Toast from '@/Components/UI/Toast.vue';
import VoucherInput from '@/Components/UI/VoucherInput.vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        props: ['href', 'method', 'as'],
        template: '<a :href="href"><slot /></a>',
    },
    usePage: () => ({
        url: '/dashboard/products',
        props: {
            auth: { user: { name: 'Admin Demo', role: 'admin' } },
            navigationGroups: [],
        },
    }),
}));

beforeAll(() => {
    globalThis.route = () => '/logout';
});

describe('UI component suite', () => {
    it('quantity stepper clamps values to minimum and maximum', async () => {
        const wrapper = mount(QuantityStepper, {
            props: { modelValue: 2, min: 1, max: 3 },
        });
        const buttons = wrapper.findAll('button');

        await buttons[0].trigger('click');
        await buttons[0].trigger('click');
        await buttons[1].trigger('click');
        await buttons[1].trigger('click');
        await wrapper.find('input').setValue('99');

        const updates = wrapper.emitted('update:modelValue').flat();
        expect(updates).toEqual([1, 1, 3, 3, 3]);
    });

    it('voucher input uppercases codes, emits apply, and shows field errors', async () => {
        const wrapper = mount(VoucherInput, {
            props: { modelValue: '', error: 'Voucher tidak valid.' },
        });

        await wrapper.find('input').setValue('sofahemat');
        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('update:modelValue')[0]).toEqual(['SOFAHEMAT']);
        expect(wrapper.emitted('apply')).toHaveLength(1);
        expect(wrapper.text()).toContain('Voucher tidak valid.');
    });

    it('status badge renders human readable labels and explicit overrides', () => {
        expect(mount(StatusBadge, { props: { status: 'menunggu_pembayaran' } }).text()).toBe('menunggu pembayaran');
        expect(mount(StatusBadge, { props: { status: 'aktif', label: 'Tersedia' } }).text()).toBe('Tersedia');
    });

    it('data table renders custom cells, actions, and empty state', () => {
        const wrapper = mount(DataTable, {
            props: {
                columns: [{ key: 'name', label: 'Nama' }, { key: 'status', label: 'Status' }],
                rows: [{ id: 1, name: 'Sofa Luna', status: 'aktif' }],
            },
            slots: {
                'cell-status': '<template #default="{ value }"><strong>{{ value }}</strong></template>',
                actions: '<template #default="{ row }"><button>Detail {{ row.id }}</button></template>',
            },
        });

        expect(wrapper.text()).toContain('Sofa Luna');
        expect(wrapper.find('strong').text()).toBe('aktif');
        expect(wrapper.text()).toContain('Detail 1');

        const empty = mount(DataTable, {
            props: { columns: [{ key: 'name', label: 'Nama' }], rows: [] },
            slots: { empty: 'Data kosong' },
        });

        expect(empty.text()).toContain('Data kosong');
    });

    it('map picker shell exposes loading, error, selected address, and action slot states', () => {
        expect(mount(MapPickerShell, { props: { loading: true } }).text()).toContain('Memuat peta');
        expect(mount(MapPickerShell, { props: { error: 'Maps gagal dimuat.' } }).text()).toContain('Maps gagal dimuat.');
        expect(mount(MapPickerShell, { props: { address: 'Jl. Contoh Sofa' } }).text()).toContain('Jl. Contoh Sofa');

        const withAction = mount(MapPickerShell, {
            slots: { actions: '<button>Pilih</button>' },
        });

        expect(withAction.text()).toContain('Pilih');
    });

    it('timeline shows completed and pending order steps', () => {
        const wrapper = mount(Timeline, {
            props: {
                steps: [
                    { label: 'Pesanan dibuat', description: 'Menunggu pembayaran', done: true },
                    { label: 'Dikirim', description: 'Belum berjalan', done: false },
                ],
            },
        });

        expect(wrapper.text()).toContain('Pesanan dibuat');
        expect(wrapper.text()).toContain('Belum berjalan');
    });

    it('alert, toast, summary card, and form input expose accessible content', async () => {
        const alert = mount(Alert, { props: { tone: 'danger' }, slots: { default: 'Terjadi kesalahan' } });
        const toast = mount(Toast, { props: { show: true, tone: 'success' }, slots: { default: 'Berhasil disimpan' } });
        const summary = mount(SummaryCard, { props: { title: 'Total Penjualan', value: 'Rp1.000.000', meta: 'Hari ini' } });
        const input = mount(FormInput, {
            props: { id: 'name', label: 'Nama', modelValue: '', error: 'Nama wajib diisi', required: true },
        });

        await input.find('input').setValue('Sofa');

        expect(alert.attributes('role')).toBe('alert');
        expect(toast.find('[role="status"]').exists()).toBe(true);
        expect(summary.text()).toContain('Rp1.000.000');
        expect(input.find('input').attributes('aria-invalid')).toBe('true');
        expect(input.emitted('update:modelValue')[0]).toEqual(['Sofa']);
    });

    it('dashboard layout renders grouped sidebar menu and active user context', () => {
        const wrapper = mount(DashboardLayout, {
            props: {
                title: 'Produk',
                navigationGroups: [
                    {
                        label: 'Produk',
                        items: [
                            { label: 'Daftar Produk', href: '/dashboard/products', icon: 'Sofa' },
                            { label: 'Kategori', href: '/dashboard/categories', icon: 'Tags' },
                        ],
                    },
                    {
                        label: 'Penjualan',
                        items: [{ label: 'Pesanan', href: '/dashboard/orders', icon: 'ShoppingBag' }],
                    },
                ],
            },
            slots: { default: '<p>Konten dashboard</p>' },
            global: {
                mocks: {
                    route: () => '/logout',
                },
            },
        });

        expect(wrapper.text()).toContain('Produk');
        expect(wrapper.text()).toContain('Daftar Produk');
        expect(wrapper.text()).toContain('Pesanan');
        expect(wrapper.text()).toContain('Admin Demo');
        expect(wrapper.text()).toContain('Konten dashboard');
    });
});
