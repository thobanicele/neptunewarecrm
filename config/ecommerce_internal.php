<?php

return [
    // If true, ecommerce features are restricted to an internal allowlist
    'only' => env('ECOMMERCE_INTERNAL_ONLY', true),

    /*
    | Comma-separated allowlist values.
    | You can put tenant subdomains OR tenant IDs.
    | Examples:
    |   "neptuneware,neptuneware-dev"
    |   "1,2,3"
    |   "neptuneware,2"
    */
    'allowed' => env('ECOMMERCE_ALLOWED_TENANTS', ''),
];