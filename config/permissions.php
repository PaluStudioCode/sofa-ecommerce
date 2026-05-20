<?php

return [
    'roles' => ['customer', 'admin', 'owner'],

    'permissions' => [
        'view_dashboard',
        'manage_products',
        'manage_product_variants',
        'manage_categories',
        'manage_vouchers',
        'manage_orders',
        'manage_payments',
        'manage_shipping_areas',
        'manage_shipments',
        'manage_landing_content',
        'view_reports',
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
            'manage_shipments',
            'manage_landing_content',
            'view_reports',
            'view_sensitive_customer_data',
            'manage_users',
        ],

        'owner' => [
            'view_dashboard',
            'view_reports',
            'view_sensitive_customer_data',
        ],
    ],
];
