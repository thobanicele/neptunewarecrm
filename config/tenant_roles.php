<?php
return [
    'roles' => [
        'tenant_owner' => ['*'],

        'tenant_admin' => [
            'users.manage', 'settings.manage',
            'leads.*','products.*', 'brands.*', 'categories.*',
            'deals.*', 'contacts.*', 'companies.*',
            'quotes.*','sales_orders.*','invoices.*', 'payments.*',
            'credit_notes.*', 'activities.*',
            'reports.view',
        ],

        'sales' => [
            'deals.*', 'contacts.*','products.view','leads.*',
            'companies.view', 'brands.view', 'categories.view',
            'quotes.*','sales_orders.*',
            'activities.*',
        ],

        'finance' => [
            'sales_orders.*','invoices.*', 'payments.*', 'credit_notes.*',
            'statements.*',
        ],

        'viewer' => [
            'deals.view','contacts.view','companies.view','products.view','leads.view',
            'quotes.view','sales_orders.view','invoices.view','reports.view', 'brands.view', 'categories.view',
        ],
    ],
];
