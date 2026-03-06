<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\PaymentTerm;

class PaymentTermSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Due on Receipt', 'days' => 0,  'sort_order' => 0],
            ['name' => 'Net 7',         'days' => 7,  'sort_order' => 10],
            ['name' => 'Net 14',        'days' => 14, 'sort_order' => 20],
            ['name' => 'Net 15',        'days' => 15, 'sort_order' => 30],
            ['name' => 'Net 30',        'days' => 30, 'sort_order' => 40],
            ['name' => 'Net 45',        'days' => 45, 'sort_order' => 50],
            ['name' => 'Net 60',        'days' => 60, 'sort_order' => 60],
        ];

        Tenant::query()->select('id')->chunkById(200, function ($tenants) use ($defaults) {
            foreach ($tenants as $tenant) {
                foreach ($defaults as $row) {
                    // Because days are unique per tenant, we can use days as our natural key
                    PaymentTerm::query()->updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'days' => (int) $row['days'],
                        ],
                        [
                            'name' => $row['name'],
                            'is_active' => true,
                            'sort_order' => (int) $row['sort_order'],
                        ]
                    );
                }
            }
        });
    }
}