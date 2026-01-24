<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\TaxType;

class TenantTaxTypeSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->select(['id'])->chunkById(200, function ($tenants) {
            foreach ($tenants as $tenant) {
                $this->seedForTenant((int) $tenant->id);
            }
        });
    }

    public static function seedForTenant(int $tenantId): void
    {
        // Standard VAT 15% (default)
        $standard = TaxType::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'Standard VAT'],
            ['rate' => 15, 'is_active' => true, 'is_default' => false]
        );

        // Zero VAT 0%
        $zero = TaxType::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'Zero VAT'],
            ['rate' => 0, 'is_active' => true, 'is_default' => false]
        );

        // Ensure there is exactly one default
        $default = TaxType::where('tenant_id', $tenantId)->where('is_default', true)->first();

        if (!$default) {
            // make Standard default if active, else any active
            if ($standard->is_active) {
                TaxType::where('tenant_id', $tenantId)->update(['is_default' => false]);
                $standard->update(['is_default' => true]);
            } else {
                $anyActive = TaxType::where('tenant_id', $tenantId)->where('is_active', true)->first();
                if ($anyActive) {
                    TaxType::where('tenant_id', $tenantId)->update(['is_default' => false]);
                    $anyActive->update(['is_default' => true]);
                }
            }
        } else {
            // If multiple defaults exist (shouldn’t, but fix it)
            TaxType::where('tenant_id', $tenantId)
                ->where('is_default', true)
                ->where('id', '!=', $default->id)
                ->update(['is_default' => false]);

            // Default cannot be inactive
            if (!$default->is_active) {
                $default->update(['is_default' => false]);
                $anyActive = TaxType::where('tenant_id', $tenantId)->where('is_active', true)->first();
                if ($anyActive) $anyActive->update(['is_default' => true]);
            }
        }
    }
}

