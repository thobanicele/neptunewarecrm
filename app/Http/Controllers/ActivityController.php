<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Contact;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $type    = (string) $request->query('type', '');
        $scope   = (string) $request->query('scope', '');
        $status  = (string) $request->query('status', 'open');
        $q       = trim((string) $request->query('q', ''));
        $showAll = (bool) $request->boolean('show_all');

        // sorting
        $sort = (string) $request->query('sort', 'due_at');
        $dir  = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        // keep this tight: allow only keys your UI uses
        $allowedSorts = ['type','subject_type','title','due_at','owner','done_at','created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'due_at';

        $now = now();
        $windowEnd = $now->copy()->addDays(14);

        $query = Activity::query()
            ->forTenant($tenant->id)
            ->whereNotNull('due_at')
            ->with(['user', 'subject']);

        // status
        if ($status === 'open') {
            $query->whereNull('done_at');
        } elseif ($status === 'done') {
            $query->whereNotNull('done_at');
        }

        // default date window: overdue + next 14 days (unless show_all=1)
        if (!$showAll) {
            $query->where(function ($qq) use ($now, $windowEnd) {
                $qq->where('due_at', '<', $now)
                ->orWhereBetween('due_at', [$now, $windowEnd]);
            });
        }

        // type
        if ($type !== '') $query->where('type', $type);

        // scope
        if ($scope === 'deal') {
            $query->where('subject_type', \App\Models\Deal::class);
        } elseif ($scope === 'contact') {
            $query->where('subject_type', \App\Models\Contact::class);
        }

        // search
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'like', "%{$q}%")
                ->orWhere('body', 'like', "%{$q}%");
            });
        }

        /**
         * ORDERING
         * Default behaviour stays: overdue first then soonest
         * But if user sorts explicitly, we honor it.
         */
        $applyDefaultPriorityOrder = ($sort === 'due_at'); // due_at default keeps your priority order

        if ($applyDefaultPriorityOrder) {
            $query->orderByRaw("
                CASE
                    WHEN done_at IS NOT NULL THEN 2
                    WHEN due_at < NOW() THEN 0
                    ELSE 1
                END
            ");
            $query->orderBy('due_at', $dir);
        } else {
            if ($sort === 'owner') {
                $query->leftJoin('users', function($j) use ($tenant) {
                        $j->on('users.id', '=', 'activities.user_id')
                        ->where('users.tenant_id', '=', $tenant->id);
                    })
                    ->select('activities.*')
                    ->orderBy('users.name', $dir);
            } elseif ($sort === 'subject_type') {
                $query->orderBy('subject_type', $dir);
            } elseif ($sort === 'done_at') {
                $query->orderBy('done_at', $dir);
            } else {
                $query->orderBy($sort, $dir);
            }

            $query->orderBy('due_at', 'asc');
        }

        $items = $query->paginate(20)->withQueryString();

        $openCount = Activity::forTenant($tenant->id)->whereNotNull('due_at')->whereNull('done_at')->count();
        $overdueCount = Activity::forTenant($tenant->id)->whereNotNull('due_at')->whereNull('done_at')->where('due_at', '<', now())->count();
        $doneCount = Activity::forTenant($tenant->id)->whereNotNull('due_at')->whereNotNull('done_at')->count();

        $canExport = tenant_feature($tenant, 'export');

        return view('tenant.activities.followups', compact(
            'tenant','items','type','scope','status','q','showAll',
            'openCount','overdueCount','doneCount',
            'sort','dir','canExport'
        ));
    }

    public function followupsExport(Request $request): StreamedResponse
    {
        $tenant = app('tenant');

        if (!tenant_feature($tenant, 'export')) {
            return back()->with('error', 'Export to Excel is available on the Premium plan.');
        }

        // same filters
        $type    = (string) $request->query('type', '');
        $scope   = (string) $request->query('scope', '');
        $status  = (string) $request->query('status', 'open');
        $q       = trim((string) $request->query('q', ''));
        $showAll = (bool) $request->boolean('show_all');

        // sorting
        $sort = (string) $request->query('sort', 'due_at');
        $dir  = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['type','subject_type','title','due_at','owner','done_at','created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'due_at';

        $now = now();
        $windowEnd = $now->copy()->addDays(14);

        $query = Activity::query()
            ->forTenant($tenant->id)
            ->whereNotNull('due_at')
            ->with(['user','subject']);

        if ($status === 'open') $query->whereNull('done_at');
        elseif ($status === 'done') $query->whereNotNull('done_at');

        if (!$showAll) {
            $query->where(function ($qq) use ($now, $windowEnd) {
                $qq->where('due_at', '<', $now)
                ->orWhereBetween('due_at', [$now, $windowEnd]);
            });
        }

        if ($type !== '') $query->where('type', $type);

        if ($scope === 'deal') $query->where('subject_type', \App\Models\Deal::class);
        elseif ($scope === 'contact') $query->where('subject_type', \App\Models\Contact::class);

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'like', "%{$q}%")
                ->orWhere('body', 'like', "%{$q}%");
            });
        }

        if ($sort === 'due_at') {
            $query->orderByRaw("
                CASE
                    WHEN done_at IS NOT NULL THEN 2
                    WHEN due_at < NOW() THEN 0
                    ELSE 1
                END
            ")->orderBy('due_at', $dir);
        } elseif ($sort === 'owner') {
            $query->leftJoin('users', function($j) use ($tenant) {
                    $j->on('users.id', '=', 'activities.user_id')
                    ->where('users.tenant_id', '=', $tenant->id);
                })
                ->select('activities.*')
                ->orderBy('users.name', $dir);
        } else {
            $query->orderBy($sort, $dir);
        }

        $rows = $query->get();

        $filename = 'activities-followups-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Type','Subject','Subject Title','Title','Notes','Due At','Status','Owner']);

            foreach ($rows as $a) {
                $isDone = !is_null($a->done_at);

                $subjectLabel = $a->subject ? class_basename($a->subject) : '';
                $subjectTitle = '';

                if ($a->subject instanceof \App\Models\Deal) {
                    $subjectLabel = 'Deal';
                    $subjectTitle = $a->subject->title ?? '';
                } elseif ($a->subject instanceof \App\Models\Contact) {
                    $subjectLabel = 'Lead';
                    $subjectTitle = $a->subject->name ?? '';
                }

                fputcsv($out, [
                    (string) $a->type,
                    $subjectLabel,
                    $subjectTitle,
                    (string) ($a->title ?? ''),
                    (string) ($a->body ?? ''),
                    optional($a->due_at)->format('Y-m-d H:i'),
                    $isDone ? 'Done' : 'Open',
                    (string) ($a->user?->name ?? ''),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

}

