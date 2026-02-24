<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;

class BackfillTenantOwners extends Command
{
    protected $signature = 'nw:backfill-tenant-owners {--dry-run}';
    protected $description = 'Set tenants.owner_user_id to earliest user in tenant where missing';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $tenants = Tenant::query()
            ->whereNull('owner_user_id')
            ->get();

        $updated = 0;

        foreach ($tenants as $t) {
            $owner = User::query()
                ->where('tenant_id', $t->id)
                ->orderBy('created_at')
                ->first();

            if (!$owner) continue;

            $this->line("Tenant {$t->id} {$t->subdomain}: owner => {$owner->email}");

            if (!$dry) {
                $t->owner_user_id = $owner->id;
                $t->save();
            }

            $updated++;
        }

        $this->info($dry ? "Dry run complete. Would update: {$updated}" : "Updated: {$updated}");
        return self::SUCCESS;
    }
}