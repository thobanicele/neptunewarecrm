<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class SetTenantPlan extends Command
{
    protected $signature = 'tenant:set-plan {subdomain} {plan}';
    protected $description = 'Set a tenant plan by subdomain';

    public function handle(): int
    {
        $subdomain = $this->argument('subdomain');
        $plan = $this->argument('plan');

        $t = Tenant::where('subdomain', $subdomain)->first();

        if (!$t) {
            $this->error('Tenant not found');
            return self::FAILURE;
        }

        $t->plan = $plan;
        $t->save();

        $this->info("Updated {$t->subdomain} to plan: {$t->plan}");
        return self::SUCCESS;
    }
}
