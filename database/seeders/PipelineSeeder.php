<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PipelineSeeder extends Seeder
{
    public function run(): void
    {
        $stageTemplate = [
            ['name' => 'New Lead',        'position' => 1, 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'Qualified',       'position' => 2, 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'Proposal Sent',   'position' => 3, 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'Negotiation',     'position' => 4, 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'Won',             'position' => 5, 'is_won' => 1, 'is_lost' => 0],
            ['name' => 'Lost',            'position' => 6, 'is_won' => 0, 'is_lost' => 1],
        ];

        Tenant::query()->select(['id'])->chunkById(200, function ($tenants) use ($stageTemplate) {
            foreach ($tenants as $tenant) {

                DB::transaction(function () use ($tenant, $stageTemplate) {

                    // Ensure only one default pipeline per tenant
                    $pipelineId = DB::table('pipelines')->where('tenant_id', $tenant->id)->where('is_default', 1)->value('id');

                    if (!$pipelineId) {
                        // Create default pipeline (or reuse existing by name)
                        $pipelineId = DB::table('pipelines')->where('tenant_id', $tenant->id)->where('name', 'Sales Pipeline')->value('id');

                        if (!$pipelineId) {
                            $pipelineId = DB::table('pipelines')->insertGetId([
                                'tenant_id' => $tenant->id,
                                'name' => 'Sales Pipeline',
                                'is_default' => 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            DB::table('pipelines')->where('id', $pipelineId)->update([
                                'is_default' => 1,
                                'updated_at' => now(),
                            ]);
                        }

                        // Turn off other defaults for this tenant
                        DB::table('pipelines')
                            ->where('tenant_id', $tenant->id)
                            ->where('id', '!=', $pipelineId)
                            ->update(['is_default' => 0, 'updated_at' => now()]);
                    }

                    // Seed stages (idempotent by tenant_id + pipeline_id + name)
                    foreach ($stageTemplate as $s) {
                        DB::table('stages')->updateOrInsert(
                            [
                                'tenant_id' => $tenant->id,
                                'pipeline_id' => $pipelineId,
                                'name' => $s['name'],
                            ],
                            [
                                'position' => $s['position'],
                                'is_won' => $s['is_won'],
                                'is_lost' => $s['is_lost'],
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    }

                    // Optional: normalize stage positions (avoid duplicates/gaps)
                    $stages = DB::table('stages')
                        ->where('tenant_id', $tenant->id)
                        ->where('pipeline_id', $pipelineId)
                        ->orderBy('position')
                        ->orderBy('id')
                        ->get(['id']);

                    $pos = 1;
                    foreach ($stages as $row) {
                        DB::table('stages')->where('id', $row->id)->update(['position' => $pos++]);
                    }
                });
            }
        });
    }
}
