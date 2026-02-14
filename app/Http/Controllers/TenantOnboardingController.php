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

        // ✅ these come from your two-button blade (hidden fields)
        $go    = (string) ($request->input('go') ?: $request->query('go', ''));
        $trial = (bool) $request->boolean('trial');          // trial=1 from button
        $cycle = (string) $request->input('cycle', 'monthly'); // monthly|yearly

        if (!in_array($cycle, ['monthly', 'yearly'], true)) {
            $cycle = 'monthly';
        }

        $tenant = DB::transaction(function () use ($data, $user, $bootstrap, $trial, $cycle) {

            // 1) Create tenant
            $tenant = Tenant::create([
                'name'      => $data['name'],
                'subdomain' => $data['subdomain'],
                'plan'      => 'free',
                'status'    => 'active',
            ]);
            $bootstrap->seedRolesForTenant($tenant->id);

            // 2) Attach user to tenant + role
            $user->forceFill(['tenant_id' => $tenant->id])->save();

            // ✅ team scope for Spatie teams
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $user->syncRoles(['tenant_owner']);

            // ✅ 2.5) Create trial subscription if requested
            if ($trial) {
                Subscription::updateOrCreate(
                    ['tenant_id' => $tenant->id],
                    [
                        'plan'       => 'premium',
                        'provider'   => 'paystack',
                        'cycle'      => $cycle,
                        'trial_ends_at' => now()->addDays(14),
                        'canceled_at'   => null,
                        'expires_at'    => null,
                    ]
                );

                // ✅ optional: unlock premium immediately during trial
                $tenant->forceFill(['plan' => 'premium'])->save();
            }

            // 3) Bootstrap (pipelines/stages/etc)
            if (method_exists($bootstrap, 'bootstrap')) {
                $bootstrap->bootstrap($tenant->id);
            }

            // 4) Resolve pipeline (fallback create)
            $pipeline = Pipeline::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->first();

            if (!$pipeline) {
                $pipeline = Pipeline::create([
                    'tenant_id' => $tenant->id,
                    'name'      => 'Sales Pipeline',
                ]);

                $this->seedStandardStages($tenant->id, $pipeline->id);
            }

            // Ensure stages exist (fallback)
            if (!PipelineStage::where('tenant_id', $tenant->id)->where('pipeline_id', $pipeline->id)->exists()) {
                $this->seedStandardStages($tenant->id, $pipeline->id);
            }

            // 5) Stage lookup helper
            $findStage = function (array $names) use ($tenant, $pipeline) {
                return PipelineStage::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('pipeline_id', $pipeline->id)
                    ->where(function ($q) use ($names) {
                        foreach ($names as $name) {
                            $q->orWhere('name', $name);
                        }
                    })
                    ->orderBy('position')
                    ->first();
            };

            $stageNew = $findStage(['New Lead', 'New', 'Lead In', 'Incoming']);
            $stageQualified = $findStage(['Qualified', 'Qualifying']);
            $stageProposal = $findStage(['Proposal Sent', 'Proposal', 'Quote Sent']);

            $fallbackStage = PipelineStage::query()
                ->where('tenant_id', $tenant->id)
                ->where('pipeline_id', $pipeline->id)
                ->orderBy('position')
                ->first();

            $stageNew ??= $fallbackStage;
            $stageQualified ??= $fallbackStage;
            $stageProposal ??= $fallbackStage;

            if ($fallbackStage) {
                Deal::insert([
                    [
                        'tenant_id' => $tenant->id,
                        'pipeline_id' => $pipeline->id,
                        'stage_id' => $stageQualified->id,
                        'title' => 'Campus lighting upgrade',
                        'amount' => 250000,
                        'expected_close_date' => now()->addDays(21)->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'pipeline_id' => $pipeline->id,
                        'stage_id' => $stageProposal->id,
                        'title' => 'Warehouse high-bay retrofit',
                        'amount' => 180000,
                        'expected_close_date' => now()->addDays(14)->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'pipeline_id' => $pipeline->id,
                        'stage_id' => $stageNew->id,
                        'title' => 'Streetlight maintenance contract',
                        'amount' => 95000,
                        'expected_close_date' => now()->addDays(30)->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }

            return $tenant;
        });

        // Redirect logic (your current logic is good)
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



