<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTenantRequest;
use App\Models\Tenant;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Services\TenantBootstrapService;
use Illuminate\Support\Facades\DB;

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

        $tenant = DB::transaction(function () use ($data, $user, $bootstrap) {

            // 1) Create tenant
            $tenant = Tenant::create([
                'name'      => $data['name'],
                'subdomain' => $data['subdomain'],
                'plan'      => 'free',
                'status'    => 'active',
            ]);

            // 2) Attach user to tenant + role so they can access tenant routes
            $user->tenant_id = $tenant->id;
            $user->save();
            $user->syncRoles(['tenant_owner']);

            /**
             * 3) Ensure pipeline + stages exist (preferred: your bootstrap service)
             *    If your service method name differs, change the call below accordingly.
             */
            if (method_exists($bootstrap, 'bootstrap')) {
                $bootstrap->bootstrap($tenant->id);
            }

            // 4) Get tenant pipeline (prefer default if you have such a flag)
            $pipeline = Pipeline::where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->first();

            // If no pipeline exists (fallback), create one + standard stages
            if (!$pipeline) {
                $pipeline = Pipeline::create([
                    'tenant_id' => $tenant->id,
                    'name'      => 'Sales Pipeline',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->seedStandardStages($tenant->id, $pipeline->id);
            }

            // Ensure stages exist (fallback if bootstrap didn't create them)
            if (!PipelineStage::where('tenant_id', $tenant->id)->where('pipeline_id', $pipeline->id)->exists()) {
                $this->seedStandardStages($tenant->id, $pipeline->id);
            }

            // 5) Resolve stage IDs by name (matches your screenshot stage names)
            $stageNew = PipelineStage::where('tenant_id', $tenant->id)
                ->where('pipeline_id', $pipeline->id)
                ->where('name', 'New Lead')
                ->first();

            $stageQualified = PipelineStage::where('tenant_id', $tenant->id)
                ->where('pipeline_id', $pipeline->id)
                ->where('name', 'Qualified')
                ->first();

            $stageProposal = PipelineStage::where('tenant_id', $tenant->id)
                ->where('pipeline_id', $pipeline->id)
                ->where('name', 'Proposal Sent')
                ->first();

            // Final fallback (should never happen if stages were seeded)
            $stageNew ??= PipelineStage::where('tenant_id', $tenant->id)->where('pipeline_id', $pipeline->id)->orderBy('position')->first();
            $stageQualified ??= $stageNew;
            $stageProposal ??= $stageNew;

            // 6) Seed demo deals using pipeline_id + stage_id (correct for your schema)
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

            return $tenant;
        });

        // Your tenant routes use {tenant:subdomain} so passing the model is best
        return redirect()
            ->route('tenant.dashboard', ['tenant' => $tenant])
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



