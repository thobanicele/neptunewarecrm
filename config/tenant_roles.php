<?php
return [
    'roles' => [
        'tenant_owner' => ['*'],

        'tenant_admin' => [
            'users.manage', 'settings.manage',
            'deals.*', 'contacts.*', 'companies.*',
            'quotes.*', 'invoices.*', 'payments.*',
            'credit_notes.*', 'activities.*',
            'reports.view',
        ],

        'sales' => [
            'deals.*', 'contacts.*',
            'companies.view',
            'quotes.*',
            'activities.*',
        ],

        'finance' => [
            'invoices.*', 'payments.*', 'credit_notes.*',
            'statements.*',
        ],

        'viewer' => [
            'deals.view','contacts.view','companies.view',
            'quotes.view','invoices.view','reports.view',
        ],
    ],
];
