<?php
return [
    'plans' => [
        'free' => [
            'deals' => ['max' => 25],
            'users' => ['max' => 3],
            'pipelines' => ['max' => 1],
            'storage_mb' => ['max' => 50],

            'features' => [
                'kanban' => true,
                'export' => false,
                'custom_branding' => false,
            ],
        ],

        'premium' => [
            'deals' => ['max' => 5000],
            'users' => ['max' => 50],
            'pipelines' => ['max' => 10],
            'storage_mb' => ['max' => 2000],

            'features' => [
                'kanban' => true,
                'export' => true,
                'custom_branding' => true,
            ],
        ],
    ],

    'default_plan' => 'free',
];




