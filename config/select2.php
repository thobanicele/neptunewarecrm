<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Select2 resources (SAFE allow-list)
    |--------------------------------------------------------------------------
    | Each resource defines:
    | - model: Eloquent model class
    | - tenant_column: column used to scope by tenant (omit/null for global tables)
    | - id: primary key column
    | - label: label column OR a closure (row => string)
    | - search: columns to "LIKE" match against (keep small + indexed)
    | - order_by: column to order by (or closure in controller if you extend)
    | - where: optional static constraints (e.g. active only)
    */
    'resources' => [

        'companies' => [
            'model' => \App\Models\Company::class,
            'tenant_column' => 'tenant_id',
            'id' => 'id',
            'label' => 'name',
            'search' => ['name', 'email', 'phone'],
            'order_by' => 'name',
        ],

        'contacts' => [
            'model' => \App\Models\Contact::class,
            'tenant_column' => 'tenant_id',
            'id' => 'id',
            'label_fields' => ['name', 'email'],
            'label_separator' => ' — ',
            'search' => ['name', 'email', 'phone'],
            'order_by' => 'name',
        ],

        'deals' => [
            'model' => \App\Models\Deal::class,
            'tenant_column' => 'tenant_id',
            'id' => 'id',
            'label' => 'title',
            'search' => ['title', 'notes'],
            'order_by' => 'title',
        ],

        'products' => [
            'model' => \App\Models\Product::class,
            'tenant_column' => 'tenant_id',
            'id' => 'id',
            // good default: "SKU — Name" (implemented via label_fields below)
            'label_fields' => ['sku', 'name'],
            'label_separator' => ' — ',
            'search' => ['sku', 'name', 'description'],
            'order_by' => 'name',
            // optional default constraints
            'where' => ['is_active' => 1],
        ],

        'brands' => [
            'model' => \App\Models\Brand::class,
            'tenant_column' => 'tenant_id',
            'id' => 'id',
            'label' => 'name',
            'search' => ['name', 'slug'],
            'order_by' => 'name',
            'where' => ['is_active' => 1],
        ],

        'categories' => [
            'model' => \App\Models\Category::class,
            'tenant_column' => 'tenant_id',
            'id' => 'id',
            'label' => 'name',
            'search' => ['name', 'slug'],
            'order_by' => 'name',
            'where' => ['is_active' => 1],
        ],

        'company_addresses' => [
            'model' => \App\Models\CompanyAddress::class,
            'tenant_column' => 'tenant_id',
            'id' => 'id',
            // label will be built from fields (label + line1 + city)
            'label_fields' => ['label', 'line1', 'city'],
            'label_separator' => ' • ',
            'search' => ['label', 'attention', 'line1', 'line2', 'city', 'postal_code', 'subdivision_text', 'phone'],
            'order_by' => 'label',
        ],

        // Global geography (no tenant scoping)
        'countries' => [
            'model' => \App\Models\Country::class,
            'tenant_column' => null,
            'id' => 'id',
            'label_fields' => ['name', 'iso2'],
            'label_separator' => ' (',
            'label_suffix' => ')',
            'search' => ['name', 'iso2', 'iso3', 'currency_code'],
            'order_by' => 'name',
        ],

        'subdivisions' => [
            'model' => \App\Models\CountrySubdivision::class,
            'tenant_column' => null,
            'id' => 'id',
            'label_fields' => ['name', 'code'],
            'label_separator' => ' (',
            'label_suffix' => ')',
            'search' => ['name', 'code', 'iso_code', 'parent_code'],
            'order_by' => 'name',
        ],

        // Users (tenant-scoped in your app via tenant_id)
        'users' => [
            'model' => \App\Models\User::class,
            'tenant_column' => 'tenant_id',
            'id' => 'id',
            'label_fields' => ['name', 'email'],
            'label_separator' => ' — ',
            'search' => ['name', 'email'],
            'order_by' => 'name',
        ],

        'payment_terms' => [
            'model' => \App\Models\PaymentTerm::class,
            'tenant_column' => 'tenant_id',
            'id' => 'id',
            'label_fields' => ['name', 'days'],
            'label_separator' => ' (',
            'label_suffix' => ' days)',
            'search' => ['name'],
            'order_by' => 'name',
        ],
    ],
];