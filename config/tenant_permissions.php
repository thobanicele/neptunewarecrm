<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Permissions (explicit)
    |--------------------------------------------------------------------------
    | We keep permissions explicit (no wildcards) so that policies/middleware
    | can rely on stable strings.
    |
    | Format: module => [actions]
    | Permission names become: "{module}.{action}" (e.g. deals.view)
    */
    'modules' => [
        'users'        => ['view', 'create', 'update', 'delete', 'manage'],
        'settings'     => ['manage'],
        'leads'        => ['view', 'create', 'update', 'delete'],
        'deals'        => ['view', 'create', 'update', 'delete'],
        'contacts'     => ['view', 'create', 'update', 'delete'],
        'companies'    => ['view', 'create', 'update', 'delete'],
        'brands'       => ['view', 'create', 'update', 'delete'],
        'categories'   => ['view', 'create', 'update', 'delete'],
        'products'     => ['view', 'create', 'update', 'delete'],

        'quotes'       => ['view', 'create', 'update', 'delete', 'send', 'accept', 'decline', 'pdf'],
        'sales_orders' => ['view', 'create', 'update', 'delete', 'issue', 'cancel', 'convert_to_invoice', 'export'],
        'invoices'     => ['view', 'create', 'update', 'delete', 'send', 'pdf'],
        'payments'     => ['view', 'create', 'update', 'delete'],
        'credit_notes' => ['view', 'create', 'update', 'delete'],
        'activities'   => ['view', 'create', 'update', 'delete'],

        'reports'      => ['view'],
        'statements'   => ['view'],
        'export'       => ['run'],
    ],
];
