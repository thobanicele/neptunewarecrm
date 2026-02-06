<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealActivity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DealController extends Controller
{
    public function __construct()
    {
        $this->middleware('tenant.limits:deals.create')->only(['create', 'store']);

        // Resource auth (index=viewAny, show=view, etc.)
        // $this->authorizeResource(Deal::class, 'deal');
    }

    public function index(Request $request)
    {
        $tenant = app('tenant');

        $stageId = $request->query('stage_id');

        $pipeline = Pipeline::where('tenant_id', $tenant->id)
            ->orderByDesc('is_default')
            ->first();

        $stages = $pipeline
            ? $pipeline->stages()->orderBy('position')->get()
            : collect();

        // Optional safety: if stageId isn't in this pipeline, ignore it
        if ($stageId && $pipeline && !$stages->pluck('id')->contains((int) $stageId)) {
            $stageId = null;
        }

        $baseDeals = Deal::query()
            ->where('tenant_id', $tenant->id)
            ->when($pipeline, fn ($q) => $q->where('pipeline_id', $pipeline->id));

        // ✅ Stats for pills (counts per stage) — uses base scope (tenant + pipeline), not filtered by stageId
        $stageStats = (clone $baseDeals)
            ->selectRaw('stage_id, COUNT(*) as cnt')
            ->groupBy('stage_id')
            ->get()
            ->keyBy('stage_id');

        // ✅ All count for "All" pill
        $allCount = (clone $baseDeals)->count();

        // ✅ Actual list (respects stage filter)
        $deals = Deal::query()
            ->where('tenant_id', $tenant->id)
            ->when($pipeline, fn ($q) => $q->where('pipeline_id', $pipeline->id))
            ->when($stageId, fn ($q) => $q->where('stage_id', $stageId))
            ->with(['stage', 'company', 'primaryContact'])
            ->withMin([
                'activities as next_followup_at' => fn ($q) =>
                    $q->whereNull('done_at')->whereNotNull('due_at')
            ], 'due_at')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();


        return view('tenant.deals.index', compact(
            'tenant',
            'stages',
            'deals',
            'stageId',
            'stageStats',
            'allCount'
        ));
    }



    public function kanban(Tenant $tenant)
{
    $tenant = app('tenant');

    $pipeline = Pipeline::where('tenant_id', $tenant->id)
        ->orderByDesc('is_default')
        ->firstOrFail();

    $stages = $pipeline->stages()->orderBy('position')->get();

    // For qualify modal (if you use it on kanban page)
    $companies = Company::where('tenant_id', $tenant->id)
        ->orderBy('name')
        ->get(['id','name']);

    // ✅ Load deals + next follow-up date (no N+1)
    $allDeals = Deal::query()
        ->where('tenant_id', $tenant->id)
        ->where('pipeline_id', $pipeline->id)
        ->with(['stage'])
        ->withMin([
            'activities as next_followup_at' => fn ($q) =>
                $q->whereNull('done_at')->whereNotNull('due_at')
        ], 'due_at')
        ->orderByDesc('amount')
        ->get();

    // ✅ Group deals by stage_id for the blade: $deals[$stageId]
    $deals = $allDeals->groupBy('stage_id');

    // Stats per stage (count + sum)
    $stats = Deal::query()
        ->selectRaw('stage_id, COUNT(*) as cnt, COALESCE(SUM(amount),0) as total_amount')
        ->where('tenant_id', $tenant->id)
        ->where('pipeline_id', $pipeline->id)
        ->groupBy('stage_id')
        ->get()
        ->keyBy('stage_id');

    $totalPipelineAmount = (float) $stats->sum('total_amount');

    return view('tenant.deals.kanban', compact(
        'tenant', 'pipeline', 'stages', 'deals', 'stats', 'totalPipelineAmount', 'companies'
    ));
}


    public function create(Request $request)
    {
        $tenant = app('tenant');

        $companies = Company::where('tenant_id', $tenant->id)->orderBy('name')->get();

        $pipelines = Pipeline::where('tenant_id', $tenant->id)->orderBy('name')->get();

        // optional lead prefill
        $lead = null;
        if ($request->filled('lead')) {
            $lead = Contact::where('tenant_id', $tenant->id)->findOrFail($request->lead);
        }

        return view('tenant.deals.create', compact('companies', 'pipelines', 'lead'));
    }

    public function store(Request $request, Tenant $tenant)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'title' => ['required','string','max:190'],
            'amount' => ['nullable','numeric','min:0'],
            'stage_id' => ['required','integer'],
            'expected_close_date' => ['nullable','date'],

            // ✅ add these
            'company_id' => ['nullable','integer','exists:companies,id'],
            'primary_contact_id' => ['nullable','integer','exists:contacts,id'], // or contacts table name
        ]);

        // ✅ tenant-safety (important): ensure selected company/contact belongs to tenant
        if (!empty($data['company_id'])) {
            $ok = Company::where('id', $data['company_id'])
                ->where('tenant_id', $tenant->id)
                ->exists();
            abort_unless($ok, 422);
        }

        if (!empty($data['primary_contact_id'])) {
            $ok = Contact::where('id', $data['primary_contact_id'])
                ->where('tenant_id', $tenant->id)
                ->exists();
            abort_unless($ok, 422);
        }

        $deal = Deal::create([
            'tenant_id' => $tenant->id,
            'pipeline_id' => $request->input('pipeline_id'), // if you use it
            'stage_id' => $data['stage_id'],
            'title' => $data['title'],
            'amount' => $data['amount'] ?? 0,
            'expected_close_date' => $data['expected_close_date'] ?? null,

            // ✅ actually save these
            'company_id' => $data['company_id'] ?? null,
            'primary_contact_id' => $data['primary_contact_id'] ?? null, // OR primary_primary_contact_id
        ]);

        return redirect()
            ->to(tenant_route('tenant.deals.show', ['tenant' => $tenant->subdomain, 'deal' => $deal->id]))
            ->with('success', 'Deal created.');
    }


    // ✅ IMPORTANT: tenant param first
    public function show(Tenant $tenant, Deal $deal)
    {
        $tenant = app('tenant');

        abort_unless((int) $deal->tenant_id === (int) $tenant->id, 404);

        $deal->load(['stage', 'company', 'primaryContact', 'activities.user']);

        $activities = $deal->activities; // optional convenience for blade

        return view('tenant.deals.show', compact('tenant', 'deal', 'activities'));
    }

   

    // ✅ IMPORTANT: tenant param first
    public function edit(Tenant $tenant, Contact $deal)
    {
        $tenant = app('tenant');
        abort_unless((int)$deal->tenant_id === (int)$tenant->id, 404);

        $pipeline = Pipeline::where('tenant_id', $tenant->id)
            ->where('id', $deal->pipeline_id)
            ->firstOrFail();

        $stages = $pipeline->stages()->orderBy('position')->get();

        return view('tenant.deals.edit', compact('tenant', 'deal', 'pipeline', 'stages'));
    }

    public function update(Request $request, Tenant $tenant, Deal $deal)
{
    $tenant = app('tenant');
    abort_unless((int)$deal->tenant_id === (int)$tenant->id, 404);

    $data = $request->validate([
        'title' => ['required','string','max:190'],
        'amount' => ['nullable','numeric','min:0'],
        'stage_id' => ['required','integer'],
        'expected_close_date' => ['nullable','date'],

        'company_id' => ['nullable','integer','exists:companies,id'],
        'primary_contact_id' => ['nullable','integer','exists:contacts,id'],
    ]);

    if (!empty($data['company_id'])) {
        $ok = \App\Models\Company::where('id', $data['company_id'])->where('tenant_id', $tenant->id)->exists();
        abort_unless($ok, 422);
    }

    if (!empty($data['primary_contact_id'])) {
        $ok = \App\Models\Contact::where('id', $data['primary_contact_id'])->where('tenant_id', $tenant->id)->exists();
        abort_unless($ok, 422);
    }

    $deal->update([
        'title' => $data['title'],
        'amount' => $data['amount'] ?? 0,
        'stage_id' => $data['stage_id'],
        'expected_close_date' => $data['expected_close_date'] ?? null,

        'company_id' => $data['company_id'] ?? null,
        'primary_contact_id' => $data['primary_contact_id'] ?? null,
    ]);

    return back()->with('success', 'Deal updated.');
}


    // ✅ IMPORTANT: tenant param first
    public function destroy(Tenant $tenant, Deal $deal)
    {
        $tenant = app('tenant');

        abort_unless((int) $deal->tenant_id === (int) $tenant->id, 404);

        $deal->delete();

        return redirect()
            ->route('tenant.deals.index', ['tenant' => $tenant])
            ->with('success', 'Deal deleted.');
    }

    /**
     * Custom endpoints (NOT part of authorizeResource) — authorize manually.
     */

    // ✅ IMPORTANT: tenant param first
    public function storeActivity(Request $request, Tenant $tenant, Deal $deal)
    {
        $tenant = app('tenant');

        $this->authorize('update', $deal);
        abort_unless((int) $deal->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        DealActivity::create([
            'tenant_id' => $tenant->id,
            'deal_id' => $deal->id,
            'user_id' => $request->user()->id,
            'type' => 'note',
            'body' => $data['body'],
        ]);

        return back()->with('success', 'Note added.');
    }

    // ✅ IMPORTANT: tenant param first
    public function addNote(Request $request, Tenant $tenant, Deal $deal)
    {
        $tenant = app('tenant');

        $this->authorize('update', $deal);
        abort_unless((int) $deal->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $activity = DealActivity::create([
            'tenant_id' => $tenant->id,
            'deal_id' => $deal->id,
            'user_id' => $request->user()?->id,
            'type' => 'note',
            'body' => $data['note'],
        ]);

        return response()->json([
            'ok' => true,
            'activity' => [
                'id' => $activity->id,
                'type' => $activity->type,
                'body' => $activity->body,
                'created_at' => $activity->created_at->toDateTimeString(),
                'user' => $request->user()?->name,
            ],
        ]);
    }

    public function updateStage(Request $request, Tenant $tenant, Deal $deal)
{
    abort_unless((int) $deal->tenant_id === (int) $tenant->id, 404);

    $data = $request->validate([
        'stage_id' => ['required', 'integer', 'exists:pipeline_stages,id'],
    ]);

    // Ensure stage belongs to the same tenant (through pipeline)
    $stage = PipelineStage::query()
        ->where('id', $data['stage_id'])
        ->whereHas('pipeline', fn ($q) => $q->where('tenant_id', $tenant->id))
        ->firstOrFail();

    return DB::transaction(function () use ($deal, $stage) {
        $oldStageId = $deal->stage_id;

        $deal->update(['stage_id' => $stage->id]);

        // Optional activity log (guard meta column)
        $payload = [
            'tenant_id' => $deal->tenant_id,
            'deal_id'   => $deal->id,
            'type'      => 'stage_changed',
            'message'   => "Stage changed to {$stage->name}",
        ];

        if (Schema::hasColumn('deal_activities', 'meta')) {
            $payload['meta'] = json_encode([
                'from_stage_id' => $oldStageId,
                'to_stage_id'   => $stage->id,
            ]);
        }

        DealActivity::create($payload);

        return response()->json([
            'ok' => true,
            'deal_id' => $deal->id,
            'stage_id' => $stage->id,
            'stage_name' => $stage->name,
        ]);
    });
}
}


