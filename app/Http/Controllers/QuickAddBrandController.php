<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Tenant;

class QuickAddBrandController extends Controller
{
    public function store(Request $request, string $tenant)
    {
        $t = tenant();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $name = trim(preg_replace('/\s+/', ' ', $data['name']));

        // Prefer matching by name (case-insensitive)
        $existing = Brand::where('tenant_id', $t->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            // ensure it's active
            if (!$existing->is_active) {
                $existing->is_active = true;
                $existing->save();
            }

            return response()->json([
                'ok' => true,
                'created' => false,
                'brand' => ['id' => $existing->id, 'name' => $existing->name],
            ], 200);
        }

        $brand = Brand::create([
            'tenant_id' => $t->id,
            'name' => $name,
            'is_active' => true,
        ]);

        return response()->json([
            'ok' => true,
            'created' => true,
            'brand' => ['id' => $brand->id, 'name' => $brand->name],
        ], 201);
    }
}
