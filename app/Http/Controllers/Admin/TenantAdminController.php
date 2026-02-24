<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Support\TenantPlan;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class TenantAdminController extends Controller
{
    public function index(Request $request)
    {
        $q        = trim((string) $request->get('q', ''));
        $plan     = trim((string) $request->get('plan', ''));
        $status   = trim((string) $request->get('status', ''));
        $activeIn = trim((string) $request->get('active_in', ''));
        $from     = trim((string) $request->get('from', ''));
        $to       = trim((string) $request->get('to', ''));
        $sort     = trim((string) $request->get('sort', 'newest'));
        $perPage  = (int) $request->get('per_page', 30);

        $perPage = in_array($perPage, [15, 30, 50, 100], true) ? $perPage : 30;

        $query = Tenant::query()
            ->when($q, function ($qq) use ($q) {
                // ✅ wrap everything in ONE group so OR doesn't break other filters
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                    ->orWhere('subdomain', 'like', "%{$q}%")
                    ->orWhereHas('owner', function ($oq) use ($q) {
                        $oq->where('email', 'like', "%{$q}%")
                            ->orWhere('name', 'like', "%{$q}%");
                    });
                });
            })
            ->when($plan, fn ($qq) => $qq->where('plan', $plan))
            ->when($status, fn ($qq) => $qq->where('status', $status))
            ->when($activeIn, fn ($qq) => $qq->where('last_seen_at', '>=', now()->subDays((int) $activeIn)))
            ->when($from, fn ($qq) => $qq->whereDate('created_at', '>=', $from))
            ->when($to, fn ($qq) => $qq->whereDate('created_at', '<=', $to))
            ->with(['owner:id,name,email'])
            ->withCount([
                'users',
                'invoices as invoices_issued_count' => fn ($q) => $q->where('status', 'issued'),
            ]);

        $query = match ($sort) {
            'oldest'    => $query->orderBy('created_at'),
            'last_seen' => $query->orderByDesc('last_seen_at'),
            'name'      => $query->orderBy('name'),
            'users'     => $query->orderByDesc('users_count'),
            'invoices'  => $query->orderByDesc('invoices_issued_count'),
            default     => $query->orderByDesc('created_at'),
        };

        $tenants = $query->paginate($perPage)->withQueryString();

        $summary = [
            'total'     => Tenant::count(),
            'active_7d' => Tenant::where('last_seen_at', '>=', now()->subDays(7))->count(),
            'new_30d'   => Tenant::where('created_at', '>=', now()->subDays(30))->count(),
            'premium'   => Tenant::where('plan', 'premium')->count(),
            'business'  => Tenant::where('plan', 'business')->count(),
        ];
        $summary['paid'] = ($summary['premium'] ?? 0) + ($summary['business'] ?? 0);

        // ✅ Pass all filters to the view (like your other index pages)
        return view('admin.tenants.index', compact(
            'tenants',
            'summary',
            'q',
            'plan',
            'status',
            'activeIn',
            'from',
            'to',
            'sort',
            'perPage'
        ));
    }

    public function show(Tenant $tenant)
    {
        // Load owner for display
        $tenant->load(['owner:id,name,email']);

        // Subscription (trial status)
        $sub = Subscription::where('tenant_id', $tenant->id)->latest()->first();

        $trialEnabled = (bool) data_get(config('plans.trial', []), 'enabled', false);
        $trialDays    = (int) data_get(config('plans.trial', []), 'days', 14);

        $trialEndsAt  = $sub?->trial_ends_at;
        $trialState   = null; // active | ended | eligible | none
        $trialDaysLeft = null;

        if ($trialEnabled && $trialEndsAt) {
            if (now()->lt($trialEndsAt)) {
                $trialState = 'active';
                $trialDaysLeft = max(1, now()->startOfDay()->diffInDays($trialEndsAt->startOfDay()));
            } else {
                $trialState = 'ended';
                $trialDaysLeft = 0;
            }
        } elseif ($trialEnabled) {
            $trialState = 'eligible';
        } else {
            $trialState = 'none';
        }

        // Effective plan (trial-aware)
        $effectivePlanKey = TenantPlan::effectivePlan($tenant);
        $plans = (array) config('plans.plans', []);
        $plan = $plans[$effectivePlanKey] ?? ($plans['free'] ?? []);

        $planLabel = data_get(
            $plans,
            "{$effectivePlanKey}.label",
            ucfirst(str_replace('_', ' ', $effectivePlanKey))
        );

        // Limits from plan config
        $limits = [
            'users' => data_get($plan, 'users.max'),
            'deals' => data_get($plan, 'deals.max'),
            'pipelines' => data_get($plan, 'pipelines.max'),
            'storage_mb' => data_get($plan, 'storage_mb.max'),
            'invoices_per_month' => data_get($plan, 'invoices.max_per_month'),
            'sales_orders_per_month' => data_get($plan, 'sales_orders.max_per_month'),
        ];

        // Usage metrics
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        $usage = [
            'users' => $tenant->users()->count(),
            'deals' => method_exists($tenant, 'deals') ? $tenant->deals()->count() : 0,
            'companies' => method_exists($tenant, 'companies') ? $tenant->companies()->count() : 0,
            'pipelines' => \App\Models\Pipeline::where('tenant_id', $tenant->id)->count(),
            'invoices_mtd' => $tenant->invoices()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count(),
            'sales_orders_mtd' => \App\Models\SalesOrder::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count(),
            'invoices_issued' => $tenant->invoices()->where('status', 'issued')->count(),
        ];

        return view('admin.tenants.show', compact(
            'tenant',
            'sub',
            'trialEnabled',
            'trialDays',
            'trialEndsAt',
            'trialState',
            'trialDaysLeft',
            'effectivePlanKey',
            'planLabel',
            'limits',
            'usage'
        ));
    }
}
