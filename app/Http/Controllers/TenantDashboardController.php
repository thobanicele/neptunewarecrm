<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TenantDashboardController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');

        // Normalize plan key (handles "Free", " free ", etc.)
        $plan = strtolower(trim($tenant->plan ?? config('tenant_limits.default_plan', 'free')));

        // If plan not defined in config, fallback to default
        $plans = config('tenant_limits.plans', []);
        if (!isset($plans[$plan])) {
            $plan = config('tenant_limits.default_plan', 'free');
        }

        // ✅ IMPORTANT: your config uses flat keys like "deals.max"
        // So we read it like this:
        $maxDeals = config("tenant_limits.plans.$plan.deals.max"); // int or null

        $dealCount = Deal::where('tenant_id', $tenant->id)->count();

        // Default pipeline
        $pipeline = Pipeline::where('tenant_id', $tenant->id)
            ->orderByDesc('is_default')
            ->first();

        // Stages
        $stages = $pipeline
            ? $pipeline->stages()->orderBy('position')->get()
            : collect();

        // Counts per stage (for default pipeline)
        $stats = Deal::where('tenant_id', $tenant->id)
            ->when($pipeline, fn ($q) => $q->where('pipeline_id', $pipeline->id))
            ->selectRaw('stage_id, COUNT(*) as cnt, COALESCE(SUM(amount),0) as total_amount')
            ->groupBy('stage_id')
            ->get()
            ->keyBy('stage_id');

        return view('tenant.dashboard.index', compact(
            'tenant',
            'plan',
            'dealCount',
            'maxDeals',
            'pipeline',
            'stages',
            'stats'
        ));
    }

    
}

