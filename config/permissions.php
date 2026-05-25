<?php

return [
    'roles' => ['customer', 'admin'],

    'permissions' => [
        'view_dashboard',
        'manage_products',
        'manage_product_variants',
        'manage_categories',
        'manage_vouchers',
        'manage_orders',
        'manage_payments',
        'manage_shipping_areas',
        'manage_system_settings',
        'view_sensitive_customer_data',
        'manage_users',
    ],

    'role_permissions' => [
        'customer' => [],

        'admin' => [
            'view_dashboard',
            'manage_products',
            'manage_product_variants',
            'manage_categories',
            'manage_vouchers',
            'manage_orders',
            'manage_payments',
            'manage_shipping_areas',
            'manage_system_settings',
            'view_sensitive_customer_data',
            'manage_users',
        ],
    ],
];
