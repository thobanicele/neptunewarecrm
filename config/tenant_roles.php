<?php
return [
    'roles' => [
        'tenant_owner' => ['*'],

        'tenant_admin' => [
            'users.manage', 'settings.manage',
            'leads.*','products.*',
            'deals.*', 'contacts.*', 'companies.*',
            'quotes.*', 'invoices.*', 'payments.*',
            'credit_notes.*', 'activities.*',
            'reports.view',
        ],

        'sales' => [
            'deals.*', 'contacts.*','products.view','leads.*',
            'companies.view',
            'quotes.*',
            'activities.*',
        ],

        'finance' => [
            'invoices.*', 'payments.*', 'credit_notes.*',
            'statements.*',
        ],

        'viewer' => [
            'deals.view','contacts.view','companies.view','products.view','leads.view',
            'quotes.view','invoices.view','reports.view',
        ],
    ],
];
