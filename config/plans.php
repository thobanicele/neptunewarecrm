<?php

return [
    'plans' => [
        'free' => [
            'deals' => ['max' => 25],
            'users' => ['max' => 3],
            'pipelines' => ['max' => 1],
            'storage_mb' => ['max' => 50],

            // ✅ limits
            'invoices' => [
                'max_per_month' => 10,
            ],

            'features' => [
                'kanban' => true,
                'export' => true,
                'custom_branding' => true,

                // ✅ invoicing
                'invoicing_manual' => true,
                'invoicing_convert_from_quote' => true,
                'invoice_pdf_watermark' => true,
                'invoice_email_send' => true,
                'statements' => true,
            ],
        ],

        'premium' => [
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
                'statments' => true,
            ],
        ],
    ],

    'default_plan' => 'free',
];

