<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Contact;
use App\Models\Tenant;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function store(Request $request)
    {
        $tenant = app('tenant');
        $user = auth()->user();

        $data = $request->validate([
            'subject_type' => ['required','string'],     // deal|contact
            'subject_id'   => ['required','integer'],
            'type'         => ['required','string','max:30'],
            'title'        => ['nullable','string','max:190'],
            'body'         => ['nullable','string'],
            'is_followup'  => ['nullable','boolean'],
            'due_at'       => ['nullable','date','required_if:is_followup,1'],
        ]);

        // Ensure boolean default
        $isFollowup = (bool) ($data['is_followup'] ?? false);

        // Normalize datetime-local format if present
        $dueAt = $data['due_at'] ?? null;
        if (is_string($dueAt)) {
            $dueAt = str_replace('T', ' ', $dueAt);
        }

        // If not follow-up, force due_at null (prevents stale values)
        if (!$isFollowup) {
            $dueAt = null;
        }

        // Clean text (optional)
        $title = isset($data['title']) ? trim((string) $data['title']) : null;
        $body  = isset($data['body']) ? trim((string) $data['body']) : null;

        [$subjectTypeClass, $subject] = $this->resolveSubject(
            $tenant->id,
            $data['subject_type'],
            (int) $data['subject_id']
        );

        Activity::create([
            'tenant_id'     => $tenant->id,
            'user_id'       => $user->id,
            'subject_type'  => $subjectTypeClass,
            'subject_id'    => $subject->id,
            'type'          => $data['type'],
            'title'         => $title ?: null,
            'body'          => $body ?: null,
            'due_at'        => $dueAt,
        ]);

        return back()->with('success', 'Activity added.');
    }


    public function toggleDone(Tenant $tenant, Activity $activity)
    {
        $tenant = app('tenant');

        abort_unless((int) $activity->tenant_id === (int) $tenant->id, 404);

        $activity->update([
            'done_at' => $activity->done_at ? null : now(),
        ]);

        return back()->with('success', $activity->done_at ? 'Marked as done.' : 'Marked as open.');
    }

    public function destroy(Tenant $tenant, Activity $activity)
    {
        $tenant = app('tenant');

        abort_unless((int) $activity->tenant_id === (int) $tenant->id, 404);

        $activity->delete();

        return back()->with('success', 'Activity deleted.');
    }

    private function resolveSubject(int $tenantId, string $kind, int $id): array
    {
        $kind = strtolower(trim($kind));

        return match ($kind) {
            'deal' => [
                Deal::class,
                Deal::where('tenant_id', $tenantId)->findOrFail($id),
            ],
            'contact', 'lead' => [
                Contact::class,
                Contact::where('tenant_id', $tenantId)->findOrFail($id),
            ],
            default => abort(422, 'Invalid subject_type'),
        };
    }

    public function followups(Request $request)
    {
        $tenant = app('tenant');

        $type   = $request->query('type');
        $scope  = $request->query('scope');
        $status = $request->query('status', 'open');
        $q      = $request->query('q');
        $showAll = (bool) $request->boolean('show_all');

        $now = now();
        $windowEnd = $now->copy()->addDays(14);

        $query = Activity::query()
            ->forTenant($tenant->id)
            ->whereNotNull('due_at')
            ->with(['user','subject']);

        // status
        if ($status === 'open') {
            $query->whereNull('done_at');
        } elseif ($status === 'done') {
            $query->whereNotNull('done_at');
        }

        // default date window: overdue + next 14 days (unless show_all=1)
        if (!$showAll) {
            $query->where(function ($qq) use ($now, $windowEnd) {
                $qq->where('due_at', '<', $now)                 // overdue
                ->orWhereBetween('due_at', [$now, $windowEnd]); // next 14 days
            });
        }

        // type
        if ($type) $query->where('type', $type);

        // scope
        if ($scope === 'deal') {
            $query->where('subject_type', \App\Models\Deal::class);
        } elseif ($scope === 'contact') {
            $query->where('subject_type', \App\Models\Contact::class);
        }

        // search
        if ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'like', "%{$q}%")
                ->orWhere('body', 'like', "%{$q}%");
            });
        }

        // order: overdue first, then soonest
        $query->orderByRaw("
            CASE
                WHEN done_at IS NOT NULL THEN 2
                WHEN due_at < NOW() THEN 0
                ELSE 1
            END
        ")->orderBy('due_at');

        $items = $query->paginate(20)->withQueryString();

        $openCount = Activity::forTenant($tenant->id)->whereNotNull('due_at')->whereNull('done_at')->count();
        $overdueCount = Activity::forTenant($tenant->id)->whereNotNull('due_at')->whereNull('done_at')->where('due_at', '<', now())->count();
        $doneCount = Activity::forTenant($tenant->id)->whereNotNull('due_at')->whereNotNull('done_at')->count();

        return view('tenant.activities.followups', compact(
            'tenant','items','type','scope','status','q','showAll',
            'openCount','overdueCount','doneCount'
        ));
    }

}

