<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tenants:seed-roles', function () {
    $service = app(\App\Services\TenantBootstrapService::class);

    $count = 0;
    foreach (\App\Models\Tenant::query()->orderBy('id')->cursor() as $t) {
        $service->seedRolesForTenant((int) $t->id);
        $count++;
        $this->info("Seeded roles/permissions for tenant #{$t->id} ({$t->subdomain})");
    }

    $this->comment("Done. Tenants processed: {$count}");
})->purpose('Seed tenant-scoped roles and global permissions for all tenants');
