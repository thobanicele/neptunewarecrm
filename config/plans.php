<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default plan for new tenants
    |--------------------------------------------------------------------------
    */
    'default_plan' => 'free',

    /*
    |--------------------------------------------------------------------------
    | Trial settings
    |--------------------------------------------------------------------------
    */
    'trial' => [
        'enabled' => true,
        'days' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing settings (Paystack)
    |--------------------------------------------------------------------------
    | Keep pricing here for now. Later we can move into DB via a Plans table.
    */
    'billing' => [
        'currency' => 'ZAR',

        // Your Paystack plan codes (created on Paystack dashboard)
        'paystack' => [
            'premium_monthly_plan_code' => env('PAYSTACK_PLAN_PREMIUM_MONTHLY'), // PLN_xxx
            'premium_yearly_plan_code'  => env('PAYSTACK_PLAN_PREMIUM_YEARLY'),

            'business_monthly_plan_code' => env('PAYSTACK_PLAN_BUSINESS_MONTHLY'), // PLN_xxx
            'business_yearly_plan_code'  => env('PAYSTACK_PLAN_BUSINESS_YEARLY'), 
            // PLN_xxx
        ],

        'users' => [
            'invites' => [
                'expires_days' => 7,
                // keep tenant_owner manual-only
                'allowed_roles' => ['tenant_admin', 'sales', 'finance', 'viewer'],
            ],
        ],

        // Pricing amounts (ZAR)
        'pricing' => [
            'premium' => [
                'monthly' => [
                    'amount' => 299.00,
                    'interval' => 'monthly',
                    'months' => 1,
                    'label' => 'Premium Monthly',
                ],
                'yearly' => [
                    'amount' => 3499.00,
                    'interval' => 'yearly',
                    'months' => 12,
                    'label' => 'Premium Yearly',
                ],
            ],
            'business' => [
                'monthly' => [
                    'amount' => 599.00,
                    'interval' => 'monthly',
                    'months' => 1,
                    'label' => 'Business Monthly',
                ],
                'yearly' => [
                    'amount' => 6999.00,
                    'interval' => 'yearly',
                    'months' => 12,
                    'label' => 'Business Yearly',
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Plan definitions (limits + features)
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'free' => [
            'label' => 'Free',

            'deals' => ['max' => 25],
            'users' => ['max' => 3],
            'pipelines' => ['max' => 1],
            'storage_mb' => ['max' => 1024],
            
            'invoices' => [
                'max_per_month' => 10,
            ],
            'sales_orders' => [
                'max_per_month' => 10,
            ],

            'features' => [
                'kanban' => true,
                'export' => false,
                'custom_branding' => false,
                'invoicing_manual' => true,
                'invoicing_convert_from_quote' => false,
                'invoice_pdf_watermark' => true,
                'invoice_email_send' => true,
                'statements' => false,
                'sales_forecasting' => false,
                'sales_analytics' => false,
                'priority_support' => false,
                'dedicated_account_manager' => false,
                'purchase_orders' => false,
                'vender_management' => false,
                'expense_tracking' => false,
                'custom_reporting' => false,
                'workflow_automation' => false,
            ],
            'features_ui' => [
                'Kanban board',
                'Quotations',
                'Limited Sales Orders & Invoices',
                'Invoice PDF watermark',
                'Invoice email sending',
            ],
        ],

        'premium' => [
            'label' => 'Premium',

            'deals' => ['max' => 5000],
            'users' => ['max' => 10],
            'pipelines' => ['max' => 10],
            'storage_mb' => ['max' => 2048],

            'invoices' => [
                'max_per_month' => null, // unlimited
            ],
            'sales_orders' => [
                'max_per_month' => null, // unlimited
            ],    

            'features' => [
                'kanban' => true,
                'export' => true,
                'custom_branding' => true,
                'invoicing_manual' => true,
                'invoicing_convert_from_quote' => true,
                'invoice_pdf_watermark' => false,
                'invoice_email_send' => true,
                'statements' => true,
                'sales_orders' => true,
                'sales_forecasting' => false,
                'sales_analytics' => false,
                'priority_support' => false,
                'dedicated_account_manager' => false,
                'purchase_orders' => false,
                'vender_management' => false,
                'expense_tracking' => false,
                'custom_reporting' => false,
                'workflow_automation' => false,
            ],
            'features_ui' => [
                'Kanban board',
                'Quotations',
                'Unlimited Invoices & Sales Orders',
                'No PDF watermark',
                'Invoice email sending',
                'Statements',
                'Payments',
                'Credit Notes',
                'Exports (Excel/CSV)',
                'Custom Branding',
                'Reports',
            ],
        ],

        'business' => [
            'label' => 'Business',

            'deals' => ['max' => 5000],
            'users' => ['max' => 20],
            'pipelines' => ['max' => 20],
            'storage_mb' => ['max' => 2048],

            'invoices' => [
                'max_per_month' => null, // unlimited
            ],
            'sales_orders' => [
                'max_per_month' => null, // unlimited
            ],

            'features' => [
                'kanban' => true,
                'export' => true,
                'custom_branding' => true,
                'invoicing_manual' => true,
                'invoicing_convert_from_quote' => true,
                'invoice_pdf_watermark' => false,
                'invoice_email_send' => true,
                'statements' => true,
                'sales_forecasting' => true,
                'sales_analytics' => true,
                'priority_support' => true,
                'dedicated_account_manager' => true,
                'purchase_orders' => true,
                'vender_management' => true,
                'expense_tracking' => true,
                'custom_reporting' => true,
                'workflow_automation' => true,
            ],
            'features_ui' => [
                'Everything in Premium',
                'Sales forecasting',
                'Sales analytics',
                'Workflow automation',
                'Custom reporting',
                'Priority support',
                'Dedicated account manager',
                'Purchase orders',
                'Vendor management',
                'Expense tracking',
            ],
        ],

        'internal_neptuneware' => [
            'label' => 'Internal (NeptuneWare)',

            'deals' => ['max' => 0],
            'users' => ['max' => 0],
            'pipelines' => ['max' => 0],
            'storage_mb' => ['max' => 2048],

            'invoices' => ['max_per_month' => null],
            'sales_orders' => ['max_per_month' => null],

            'features' => [
                'ecommerce_module' => true,
                'ecommerce_inbound_api' => true,
                'export' => true,
                'custom_branding' => true,
            ],
        ],
    ], 
];

