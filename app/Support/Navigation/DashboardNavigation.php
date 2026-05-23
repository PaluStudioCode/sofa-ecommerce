<?php

namespace App\Support\Navigation;

use App\Models\User;

class DashboardNavigation
{
    public static function forUser(User $user): array
    {
        return match ($user->role) {
            'admin' => self::admin(),
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
                    self::item('Aturan Ongkir Radius', 'admin.shipping-areas.index', '/dashboard/shipping-areas', 'Map', 'manage_shipping_areas'),
                    self::item('Pengiriman Internal', 'admin.shipments.index', '/dashboard/shipments', 'Truck', 'manage_shipments'),
                ],
            ],
            [
                'label' => 'Pengguna',
                'items' => [
                    self::item('Pengguna', 'admin.users.index', '/dashboard/users', 'Users', 'manage_users'),
                ],
            ],
        ];
    }

    private static function item(string $label, string $route, string $href, string $icon, string $permission): array
    {
        return compact('label', 'route', 'href', 'icon', 'permission');
    }
}
