<?php

namespace App\Services;

use App\Models\Pipeline;
use App\Models\PipelineStage;

class TenantBootstrapService
{
    public function bootstrap(int $tenantId): void
    {
        $pipeline = Pipeline::create([
            'tenant_id' => $tenantId,
            'name' => 'Sales Pipeline',
            'is_default' => true,
        ]);

        $stages = [
            ['name' => 'New Lead',        'position' => 1],
            ['name' => 'Qualified',  'position' => 2],
            ['name' => 'Proposal Sent',   'position' => 3],
            ['name' => 'Negotiation','position' => 4],
            ['name' => 'Won',        'position' => 5, 'is_won' => true],
            ['name' => 'Lost',       'position' => 6, 'is_lost' => true],
        ];

        foreach ($stages as $s) {
            PipelineStage::create([
                'tenant_id' => $tenantId,
                'pipeline_id' => $pipeline->id,
                'name' => $s['name'],
                'position' => $s['position'],
                'is_won' => $s['is_won'] ?? false,
                'is_lost' => $s['is_lost'] ?? false,
            ]);
        }
    }
}

