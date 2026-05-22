<?php

namespace App\Support\Navigation;

use App\Models\User;

class DashboardNavigation
{
    public static function forUser(User $user): array
    {
        return match ($user->role) {
            'admin' => self::admin(),
            'owner' => self::owner(),
            default => [],
        };
    }

    private static function admin(): array
    {
        return [
            [
                'label' => 'Ringkasan',
                'items' => [
                    self::item('Dashboard', 'dashboard', '/dashboard', 'LayoutDashboard', 'view_dashboard'),
                ],
            ],
            [
                'label' => 'Produk',
                'items' => [
                    self::item('Produk', 'admin.products.index', '/dashboard/products', 'Sofa', 'manage_products'),
                    self::item('Kategori', 'admin.categories.index', '/dashboard/categories', 'Tags', 'manage_categories'),
                    self::item('Varian dan Stok', 'admin.variants.index', '/dashboard/variants', 'Boxes', 'manage_product_variants'),
                    self::item('Gambar Produk', 'admin.product-images.index', '/dashboard/product-images', 'Images', 'manage_products'),
                ],
            ],
            [
                'label' => 'Penjualan',
                'items' => [
                    self::item('Pesanan', 'admin.orders.index', '/dashboard/orders', 'ShoppingBag', 'manage_orders'),
                    self::item('Pembayaran', 'admin.payments.index', '/dashboard/payments', 'CreditCard', 'manage_payments'),
                    self::item('Voucher', 'admin.vouchers.index', '/dashboard/vouchers', 'TicketPercent', 'manage_vouchers'),
                ],
            ],
            [
                'label' => 'Pengiriman',
                'items' => [
                    self::item('Toko & Radius Layanan', 'admin.shipping-areas.index', '/dashboard/shipping-areas', 'Map', 'manage_shipping_areas'),
                    self::item('Pengiriman Internal', 'admin.shipments.index', '/dashboard/shipments', 'Truck', 'manage_shipments'),
                ],
            ],
            [
                'label' => 'Konten',
                'items' => [
                    self::item('Landing Page', 'admin.landing-sections.index', '/dashboard/landing-sections', 'PanelTop', 'manage_landing_content'),
                ],
            ],
            [
                'label' => 'Pengguna',
                'items' => [
                    self::item('Pengguna', 'admin.users.index', '/dashboard/users', 'Users', 'manage_users'),
                    self::item('Role', 'admin.roles.index', '/dashboard/roles', 'ShieldCheck', 'manage_users'),
                ],
            ],
        ];
    }

    private static function owner(): array
    {
        return [
            [
                'label' => 'Ringkasan',
                'items' => [
                    self::item('Dashboard', 'dashboard', '/dashboard', 'LayoutDashboard', 'view_dashboard'),
                ],
            ],
            [
                'label' => 'Laporan',
                'items' => [
                    self::item('Penjualan', 'owner.reports.sales', '/dashboard/reports/sales', 'ChartNoAxesCombined', 'view_reports'),
                    self::item('Produk Terjual', 'owner.reports.products', '/dashboard/reports/products', 'PackageCheck', 'view_reports'),
                    self::item('Voucher', 'owner.reports.vouchers', '/dashboard/reports/vouchers', 'TicketPercent', 'view_reports'),
                    self::item('Biaya Pengiriman', 'owner.reports.shipping', '/dashboard/reports/shipping', 'Truck', 'view_reports'),
                ],
            ],
            [
                'label' => 'Monitoring',
                'items' => [
                    self::item('Pesanan', 'owner.monitoring.orders', '/dashboard/monitoring/orders', 'ShoppingBag', 'view_dashboard'),
                    self::item('Pembayaran', 'owner.monitoring.payments', '/dashboard/monitoring/payments', 'CreditCard', 'view_dashboard'),
                    self::item('Pengiriman', 'owner.monitoring.shipments', '/dashboard/monitoring/shipments', 'MapPinned', 'view_dashboard'),
                ],
            ],
        ];
    }

    private static function item(string $label, string $route, string $href, string $icon, string $permission): array
    {
        return compact('label', 'route', 'href', 'icon', 'permission');
    }
}
