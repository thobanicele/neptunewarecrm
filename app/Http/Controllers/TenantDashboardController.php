<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Transactionallocation;
use App\Models\Pipeline;
use App\Models\Quote;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;


class TenantDashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        // -----------------------------
        // Plan normalize
        // -----------------------------
        $plan = strtolower(trim($tenant->plan ?? config('plans.default_plan', 'free')));
        $plans = config('plans.plans', []);
        if (!isset($plans[$plan])) {
            $plan = config('plans.default_plan', 'free');
        }
        $maxDeals = config("plans.plans.$plan.deals.max");
        $dealCount = Deal::where('tenant_id', $tenant->id)->count();

        // -----------------------------
        // Default pipeline + stages
        // -----------------------------
        $pipeline = Pipeline::where('tenant_id', $tenant->id)
            ->orderByDesc('is_default')
            ->first();

        $stages = $pipeline
            ? $pipeline->stages()->orderBy('position')->get()
            : collect();

        // -----------------------------
        // Pipeline stats (OPEN deals = not won/lost stages)
        // -----------------------------
        $closedStageIds = $pipeline
            ? $pipeline->stages()
                ->where(function ($q) {
                    $q->where('is_won', 1)->orWhere('is_lost', 1);
                })
                ->pluck('id')
            : collect();

        $openDealsQuery = Deal::query()
            ->where('tenant_id', $tenant->id)
            ->when($pipeline, fn ($q) => $q->where('pipeline_id', $pipeline->id))
            ->when($closedStageIds->isNotEmpty(), fn ($q) => $q->whereNotIn('stage_id', $closedStageIds->all()));

        $openDeals = (clone $openDealsQuery)->count();
        $pipelineValue = (float) (clone $openDealsQuery)->sum('amount');

        // counts+value per stage (for table)
        $stats = (clone $openDealsQuery)
            ->selectRaw('stage_id, COUNT(*) as cnt, COALESCE(SUM(amount),0) as total_amount')
            ->groupBy('stage_id')
            ->get()
            ->keyBy('stage_id');

        // build pipelineByStage rows for the blade
        $pipelineByStage = $stages->map(function ($s) use ($stats) {
            $row = $stats->get($s->id);
            return [
                'stage' => $s->name,
                'count' => (int) data_get($row, 'cnt', 0),
                'value' => (float) data_get($row, 'total_amount', 0),
            ];
        })->values()->all();

        // -----------------------------
        // Invoices Outstanding (Issued + unpaid/partial)
        // -----------------------------
        $invoicesOutstanding = (float) \App\Models\Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNull('voided_at')
            ->sum('total');

        // -----------------------------
        // Overdue invoices (Issued + unpaid/partial + due_at < today)
        // -----------------------------
        $overdueInvoicesQuery = \App\Models\Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereNull('voided_at')
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', now()->toDateString());

        $overdueInvoicesCount = (int) (clone $overdueInvoicesQuery)->count();
        $overdueInvoicesTotal = (float) (clone $overdueInvoicesQuery)->sum('total');

        $invoicesToChase = (clone $overdueInvoicesQuery)
            ->with(['company:id,name'])
            ->orderBy('due_at')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // -----------------------------
        // Cash collected MTD + trend (Transaction allocations)
        // NOTE: adjust model name if yours differs (TransactionAllocation)
        // -----------------------------
        $monthStart = now()->startOfMonth();

        $cashCollectedMtd = (float) \App\Models\TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $monthStart)
            ->sum('amount_applied');

        $start = now()->subDays(29)->startOfDay();

        $trendRows = \App\Models\TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, COALESCE(SUM(amount_applied),0) as total')
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $cashTrendLabels = [];
        $cashTrendData = [];
        for ($i = 0; $i < 30; $i++) {
            $d = $start->copy()->addDays($i)->toDateString();
            $cashTrendLabels[] = $d;
            $cashTrendData[] = (float) data_get($trendRows->get($d), 'total', 0);
        }

        // -----------------------------
        // Sales orders pending fulfillment
        // -----------------------------
        $soPendingQuery = SalesOrder::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['cancelled', 'canceled']); // adjust if you have exact statuses

        $soPendingCount = (int) (clone $soPendingQuery)->count();

        $soPending = (clone $soPendingQuery)
            ->with(['company:id,name'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // -----------------------------
        // Quotes expiring soon (next 7 days)
        // -----------------------------
        $now = now();
        $soon = now()->addDays(7);

        $quotesExpiring = Quote::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['draft', 'sent', 'accepted'])
            ->where(function ($q) use ($now, $soon) {
                // keep both in case your schema has one of them
                $q->whereBetween('valid_until', [$now->toDateString(), $soon->toDateString()])
                ->orWhereBetween('valid_until', [$now->toDateString(), $soon->toDateString()]);
            })
            ->with(['company:id,name'])
            ->orderByRaw('COALESCE(valid_until, valid_until) asc')
            ->limit(10)
            ->get();

        // -----------------------------
        // Followups
        // -----------------------------
        $followups = Activity::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('done_at')
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->limit(10)
            ->get();

        // -----------------------------
        // Existing charts
        // -----------------------------
        $months = $this->last12MonthsLabels();
        $quotesSeries = $this->quotesBySalesPersonSeries($tenant->id, $months);
        $invoicesSeries = $this->invoicesBySalesPersonSeries($tenant->id, $months);

        logger()->info('DASHBOARD OVERDUE DEBUG', [
            'tenant_id' => $tenant->id,
            'overdue_count' => $overdueInvoicesCount,
            'overdue_total' => $overdueInvoicesTotal,
        ]);

        return view('tenant.dashboard.index', compact(
            'tenant',
            'plan',
            'dealCount',
            'maxDeals',

            'pipeline',
            'stages',
            'stats',

            // top row
            'openDeals',
            'pipelineValue',
            'invoicesOutstanding',
            'overdueInvoicesCount',
            'overdueInvoicesTotal',
            'cashCollectedMtd',
            'soPendingCount',

            // middle row
            'pipelineByStage',
            'cashTrendLabels',
            'cashTrendData',

            // bottom row
            'followups',
            'invoicesToChase',
            'quotesExpiring',
            'soPending',

            // charts row
            'months',
            'quotesSeries',
            'invoicesSeries',
        ));
    }

    private function last12MonthsLabels(): array
    {
        $out = [];
        $start = now()->startOfMonth()->subMonths(11);
        for ($i = 0; $i < 12; $i++) {
            $out[] = $start->copy()->addMonths($i)->format('Y-m');
        }
        return $out;
    }

    private function quotesBySalesPersonSeries(int $tenantId, array $months): array
    {
        // Map months to indexes
        $monthIndex = [];
        foreach ($months as $i => $m) {
            $monthIndex[$m] = $i;
        }

        // Get totals per (month, sales_person_user_id)
        // Uses created_at because quotes table doesn't always have issued_at; adjust if you have a better date column.
        $rows = Quote::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('sales_person_user_id')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, sales_person_user_id as uid, COALESCE(SUM(subtotal),0) as total")
            ->groupBy('ym', 'uid')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Resolve salesperson names
        $userIds = $rows->pluck('uid')->unique()->values();
        $names = User::query()
            ->whereIn('id', $userIds)
            ->pluck('name', 'id');

        // Build series per user
        $series = [];
        foreach ($userIds as $uid) {
            $data = array_fill(0, count($months), 0);

            foreach ($rows->where('uid', $uid) as $r) {
                $ym = (string) $r->ym;
                if (!isset($monthIndex[$ym])) continue;
                $data[$monthIndex[$ym]] = (float) $r->total;
            }

            $series[] = [
                'label' => (string) ($names[$uid] ?? ('User #' . $uid)),
                'data' => $data,
            ];
        }

        return $series;
    }

    private function invoicesBySalesPersonSeries(int $tenantId, array $months): array
    {
        $monthIndex = [];
        foreach ($months as $i => $m) {
            $monthIndex[$m] = $i;
        }

        $rows = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'issued') // matches your system
            ->whereNotNull('sales_person_user_id')
            ->selectRaw("DATE_FORMAT(issued_at, '%Y-%m') as ym, sales_person_user_id as uid, COALESCE(SUM(total),0) as total")
            ->groupBy('ym', 'uid')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $userIds = $rows->pluck('uid')->unique()->values();
        $names = User::query()
            ->whereIn('id', $userIds)
            ->pluck('name', 'id');

        $series = [];
        foreach ($userIds as $uid) {
            $data = array_fill(0, count($months), 0);

            foreach ($rows->where('uid', $uid) as $r) {
                $ym = (string) $r->ym;
                if (!isset($monthIndex[$ym])) continue;
                $data[$monthIndex[$ym]] = (float) $r->total;
            }

            $series[] = [
                'label' => (string) ($names[$uid] ?? ('User #' . $uid)),
                'data' => $data,
            ];
        }

        return $series;
    }
}

