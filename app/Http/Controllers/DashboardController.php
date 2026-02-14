<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\CreditNote;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote; // ✅ add this
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(string $tenantKey)
    {
        $tenant = app('tenant');

        // -----------------------------
        // Deals: "open" = stage is NOT won and NOT lost
        // -----------------------------
        $openDealsBase = Deal::query()
            ->where('deals.tenant_id', $tenant->id)
            ->join('pipeline_stages as ps', 'ps.id', '=', 'deals.stage_id')
            ->where('ps.is_won', 0)
            ->where('ps.is_lost', 0);

        $openDeals = (int) (clone $openDealsBase)->count();
        $pipelineValue = (float) (clone $openDealsBase)->sum('deals.amount');

        // Pipeline snapshot by stage (open only)
        $pipelineSnapshot = Deal::query()
            ->where('deals.tenant_id', $tenant->id)
            ->join('pipeline_stages as ps', 'ps.id', '=', 'deals.stage_id')
            ->where('ps.is_won', 0)
            ->where('ps.is_lost', 0)
            ->groupBy('ps.id', 'ps.name', 'ps.position')
            ->orderBy('ps.position')
            ->get([
                'ps.name',
                DB::raw('COUNT(deals.id) as deals_count'),
                DB::raw('COALESCE(SUM(deals.amount),0) as deals_value'),
            ]);

        $recentDeals = Deal::query()
            ->where('tenant_id', $tenant->id)
            ->with(['company', 'stage'])
            ->latest('updated_at')
            ->limit(8)
            ->get();

        // -----------------------------
        // Invoices outstanding: issued AND unpaid/partial
        // Outstanding = invoice.total - sum(transaction_allocations.amount_applied)
        // -----------------------------
        $allocSub = DB::table('transaction_allocations')
            ->selectRaw('invoice_id, SUM(amount_applied) AS allocated')
            ->where('tenant_id', $tenant->id)
            ->groupBy('invoice_id');

        $invoicesOutstanding = (float) Invoice::query()
            ->from('invoices') // ✅ ensure base table name
            ->where('invoices.tenant_id', $tenant->id)
            ->where('invoices.status', 'issued')
            ->whereIn('invoices.payment_status', ['unpaid', 'partial'])
            ->leftJoinSub($allocSub, 'alloc', function ($join) {
                $join->on('alloc.invoice_id', '=', 'invoices.id');
            })
            ->selectRaw('COALESCE(SUM(GREATEST(invoices.total - COALESCE(alloc.allocated,0), 0)), 0) AS outstanding')
            ->value('outstanding');

        $invoicesToChase = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'issued')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->with('company')
            ->orderByRaw('due_at IS NULL') // ✅ push NULL due dates last (optional)
            ->orderBy('due_at')
            ->limit(8)
            ->get();

        // -----------------------------
        // Cash collected (MTD): payment allocations only
        // transaction_allocations.applied_at + payment_id != null
        // -----------------------------
        $startOfMonth = now()->startOfMonth()->toDateString();

        $cashCollectedMtd = (float) DB::table('transaction_allocations')
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('payment_id')              // ✅ payment allocations
            ->whereDate('applied_at', '>=', $startOfMonth) // ✅ applied_at
            ->sum('amount_applied');

        // -----------------------------
        // Overdue + due soon activities
        // -----------------------------
        $overdueActivities = Activity::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('due_at')
            ->whereNull('done_at')
            ->where('due_at', '<', now())
            ->count();

        $dueSoonActivities = Activity::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('due_at')
            ->whereNull('done_at')
            ->whereBetween('due_at', [now(), now()->copy()->addDays(7)])
            ->count();

        $followups = Activity::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('due_at')
            ->whereNull('done_at')
            ->where(function ($q) {
                $q->where('due_at', '<', now())
                    ->orWhereBetween('due_at', [now(), now()->copy()->addDays(14)]);
            })
            ->with(['user', 'subject'])
            ->orderBy('due_at')
            ->limit(8)
            ->get();

        // -----------------------------
        // Unallocated funds: Payments + Credits
        // Payments unallocated = sum(payments.amount) - sum(allocations where payment_id != null)
        // Credits available = credit_notes.amount - allocated - refunded
        // -----------------------------
        $paymentsTotal = (float) Payment::query()
            ->where('tenant_id', $tenant->id)
            ->sum('amount');

        $paymentsAllocated = (float) DB::table('transaction_allocations')
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('payment_id') // ✅ payment allocations
            ->sum('amount_applied');

        $paymentsUnallocated = max(0, $paymentsTotal - $paymentsAllocated);

        $creditsTotal = (float) CreditNote::query()
            ->where('tenant_id', $tenant->id)
            ->sum('amount');

        $creditsAllocated = (float) DB::table('transaction_allocations')
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('credit_note_id') // ✅ credit allocations
            ->sum('amount_applied');

        $creditsRefunded = (float) DB::table('credit_note_refunds')
            ->where('tenant_id', $tenant->id)
            ->sum('amount');

        $creditsAvailable = max(0, $creditsTotal - $creditsAllocated - $creditsRefunded);

        // -----------------------------
        // Graphs (last 12 months): Quotes + Invoices by salesperson
        // -----------------------------
        $months = collect(range(0, 11))
            ->map(fn ($i) => now()->copy()->subMonths(11 - $i)->format('Y-m'))
            ->values();

        $start12 = now()->copy()->subMonths(11)->startOfMonth();

        $quoteAgg = Quote::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('sales_person_user_id')
            ->where('created_at', '>=', $start12)
            ->selectRaw("sales_person_user_id, DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt, SUM(subtotal) as subtotal_sum")
            ->groupBy('sales_person_user_id', 'ym')
            ->get();

        $invoiceAgg = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('sales_person_user_id')
            ->whereNotNull('issued_at')
            ->where('issued_at', '>=', $start12)
            ->selectRaw("sales_person_user_id, DATE_FORMAT(issued_at, '%Y-%m') as ym, COUNT(*) as cnt, SUM(total) as total_sum")
            ->groupBy('sales_person_user_id', 'ym')
            ->get();

        $salesUserIds = $quoteAgg->pluck('sales_person_user_id')
            ->merge($invoiceAgg->pluck('sales_person_user_id'))
            ->unique()
            ->values()
            ->all();

        $salesPeople = DB::table('users')
            ->whereIn('id', $salesUserIds)
            ->pluck('name', 'id');

        $quotesSeries = $this->buildSeries($months, $quoteAgg, $salesPeople, 'subtotal_sum');
        $invoicesSeries = $this->buildSeries($months, $invoiceAgg, $salesPeople, 'total_sum');

        return view('tenant.dashboard.index', compact(
            'tenant',
            'openDeals', 'pipelineValue', 'pipelineSnapshot', 'recentDeals',
            'invoicesOutstanding', 'cashCollectedMtd',
            'overdueActivities', 'dueSoonActivities',
            'paymentsUnallocated', 'creditsAvailable',
            'followups', 'invoicesToChase',
            'months', 'quotesSeries', 'invoicesSeries'
        ));
    }

    private function buildSeries($months, $rows, $salesPeople, string $valueField): array
    {
        $byUser = $rows->groupBy('sales_person_user_id');

        $series = [];
        foreach ($byUser as $userId => $items) {
            $map = $items->keyBy('ym');

            $data = $months->map(function ($m) use ($map, $valueField) {
                $row = $map->get($m);
                return $row ? (float) $row->{$valueField} : 0.0;
            })->values()->all();

            $series[] = [
                'label' => $salesPeople[$userId] ?? ("User #{$userId}"),
                'data'  => $data,
            ];
        }

        return $series;
    }
}

