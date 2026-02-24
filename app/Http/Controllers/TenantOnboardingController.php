<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTenantRequest;
use App\Models\Tenant;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Services\TenantBootstrapService;
use Illuminate\Support\Facades\DB;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;


class TenantOnboardingController extends Controller
{
    public function create()
    {
        return view('tenant.onboarding.create');
    }

    public function store(CreateTenantRequest $request, TenantBootstrapService $bootstrap)
    {
        $user = $request->user();
        $data = $request->validated();

        $go    = (string) ($request->input('go') ?: $request->query('go', ''));
        $trial = (bool) $request->boolean('trial');
        $cycle = (string) $request->input('cycle', 'monthly');

        if (!in_array($cycle, ['monthly', 'yearly'], true)) {
            $cycle = 'monthly';
        }

        $tenant = DB::transaction(function () use ($data, $user, $bootstrap, $trial, $cycle) {

            $tenant = Tenant::create([
                'name'      => $data['name'],
                'subdomain' => $data['subdomain'],
                'plan'      => 'free',
                'status'    => 'active',
            ]);

            // Ensure roles exist (idempotent)
            $bootstrap->seedRolesForTenant((int) $tenant->id);

            // Attach user to tenant
            $user->forceFill([
                'tenant_id'  => $tenant->id,
                'is_active'  => true,
            ])->save();

            // ✅ Set tenant owner (platform-level)
            $tenant->forceFill(['owner_user_id' => $user->id])->save();

            // Assign role under correct team scope
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $user->syncRoles(['tenant_owner']);

            // Trial
            if ($trial) {
                Subscription::updateOrCreate(
                    ['tenant_id' => $tenant->id],
                    [
                        'plan'          => 'premium',
                        'provider'      => 'paystack',
                        'cycle'         => $cycle,
                        'trial_ends_at' => now()->addDays(14),
                        'canceled_at'   => null,
                        'expires_at'    => null,
                    ]
                );

                $tenant->forceFill(['plan' => 'premium'])->save();
            }

            // ... keep your pipeline bootstrap logic as-is here ...

            return $tenant;
        });

        if ($go === 'upgrade') {
            return redirect()
                ->route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain])
                ->with('success', 'Workspace created! Start your 14-day trial by upgrading to Premium.');
        }

        return redirect()
            ->route('tenant.dashboard', ['tenant' => $tenant->subdomain])
            ->with('success', 'Workspace created successfully!');
    }

    



    private function seedStandardStages(int $tenantId, int $pipelineId): void
    {
        $now = now();

        PipelineStage::insert([
            [
                'tenant_id' => $tenantId,
                'pipeline_id' => $pipelineId,
                'name' => 'New Lead',
                'position' => 1,
                'is_won' => 0,
                'is_lost' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tenant_id' => $tenantId,
                'pipeline_id' => $pipelineId,
                'name' => 'Qualified',
                'position' => 2,
                'is_won' => 0,
                'is_lost' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tenant_id' => $tenantId,
                'pipeline_id' => $pipelineId,
                'name' => 'Proposal Sent',
                'position' => 3,
                'is_won' => 0,
                'is_lost' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tenant_id' => $tenantId,
                'pipeline_id' => $pipelineId,
                'name' => 'Negotiation',
                'position' => 4,
                'is_won' => 0,
                'is_lost' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tenant_id' => $tenantId,
                'pipeline_id' => $pipelineId,
                'name' => 'Won',
                'position' => 5,
                'is_won' => 1,
                'is_lost' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tenant_id' => $tenantId,
                'pipeline_id' => $pipelineId,
                'name' => 'Lost',
                'position' => 6,
                'is_won' => 0,
                'is_lost' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}



