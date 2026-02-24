<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HMAC settings for inbound ecommerce → CRM
    |--------------------------------------------------------------------------
    */

    // Allowed clock skew / replay window in seconds (default 5 minutes)
    'signature_window_seconds' => env('ECOMMERCE_API_SIGNATURE_WINDOW', 300),

    // Where to store used nonces (Cache store name; null = default)
    'nonce_cache_store' => env('ECOMMERCE_API_NONCE_CACHE_STORE', null),

    // Prefix for nonce keys in cache
    'nonce_cache_prefix' => env('ECOMMERCE_API_NONCE_CACHE_PREFIX', 'ecomm_api_nonce:'),

    // If true, require HTTPS in production for signed requests (recommended)
    'require_https_in_production' => env('ECOMMERCE_API_REQUIRE_HTTPS', true),

    /*
    |--------------------------------------------------------------------------
    | Signature format
    |--------------------------------------------------------------------------
    | We sign:
    |   tenant_key \n
    |   timestamp  \n
    |   nonce      \n
    |   METHOD     \n
    |   PATH?QUERY \n
    |   SHA256(body)
    |
    | Signature header:
    |   X-Signature: base64_encode(hmac_sha256(canonical, secret, raw=true))
    */
];