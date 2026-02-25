<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL'), '/') . '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Generic S3 (also used for R2 when you supply endpoint)
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),

            // IMPORTANT: region must be a real string for AWS SDK (R2 often uses us-east-1)
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),

            'bucket' => env('AWS_BUCKET'),

            // If set, Storage::url() will use this as the base public URL
            'url' => env('AWS_URL'),

            // For R2 / custom S3-compatible providers
            'endpoint' => env('AWS_ENDPOINT'),

            // R2 commonly works best with path-style endpoints
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),

            'throw' => false,
            'report' => false,
        ],

        // Tenant logo disk (R2/S3)
        'tenant_logos' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),

            // IMPORTANT: region must be a real string for AWS SDK
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),

            'bucket' => env('AWS_BUCKET'),

            // Make sure this is your PUBLIC base URL for the bucket (Laravel Cloud provides one)
            'url' => env('AWS_URL'),

            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),

            // Needed for direct <img src="..."> URLs (if your bucket allows public read)
            'visibility' => 'public',

            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
