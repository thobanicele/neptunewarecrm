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
            'premium_yearly_plan_code'  => env('PAYSTACK_PLAN_PREMIUM_YEARLY'),  // PLN_xxx
        ],

        'users' => [
            'invites' => [
                'expires_days' => 7,
                'allowed_roles' => ['tenant_admin', 'tenant_staff'], // keep tenant_owner manual-only
            ],
        ],

        // Pricing amounts (ZAR)
        'pricing' => [
            'premium' => [
                'monthly' => [
                    'amount' => 199.00,
                    'interval' => 'monthly',
                    'months' => 1,
                    'label' => 'Premium Monthly',
                ],
                'yearly' => [
                    'amount' => 1990.00,
                    'interval' => 'yearly',
                    'months' => 12,
                    'label' => 'Premium Yearly',
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
            'storage_mb' => ['max' => 50],

            'invoices' => [
                'max_per_month' => 10,
            ],

            'features' => [
                'kanban' => true,
                'export' => false,
                'custom_branding' => true,

                'invoicing_manual' => true,
                'invoicing_convert_from_quote' => false,
                'invoice_pdf_watermark' => true,
                'invoice_email_send' => true,
                'statements' => false,
            ],
        ],

        'premium' => [
            'label' => 'Premium',

            'deals' => ['max' => 5000],
            'users' => ['max' => 50],
            'pipelines' => ['max' => 10],
            'storage_mb' => ['max' => 2000],

            'invoices' => [
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
            ],
        ],
    ],
];

