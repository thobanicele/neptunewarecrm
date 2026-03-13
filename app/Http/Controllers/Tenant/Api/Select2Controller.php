<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Select2Controller extends Controller
{
    
    protected function cfg(string $resource): array
    {
        $resource = trim((string) $resource);
        $resources = config('select2.resources', []);
        
        $cfg = $resources[$resource] ?? null;

        abort_unless(is_array($cfg), 404, 'Unknown Select2 resource');
        abort_unless(!empty($cfg['model']), 500, 'Select2 resource misconfigured');

        return $cfg;
    }

    protected function buildLabel($row, array $cfg): string
    {
        // 1) direct label column
        if (!empty($cfg['label'])) {
            $col = $cfg['label'];
            return (string) ($row->{$col} ?? '');
        }

        // 2) label_fields composition
        $fields = (array)($cfg['label_fields'] ?? []);
        $sep = (string)($cfg['label_separator'] ?? ' ');
        $suffix = (string)($cfg['label_suffix'] ?? '');

        $parts = [];
        foreach ($fields as $f) {
            $v = trim((string)($row->{$f} ?? ''));
            if ($v !== '') $parts[] = $v;
        }

        if (!$parts) return (string)($row->id ?? '');

        // special: if separator is " (" and suffix ")"
        // our config uses separator '(' without space in some spots; keep it simple
        if ($suffix !== '' && str_contains($sep, '(')) {
            // first part is name, second part goes in parentheses
            $first = array_shift($parts);
            $rest = implode(' ', $parts);
            return $rest ? ($first . $sep . $rest . $suffix) : $first;
        }

        return implode($sep, $parts) . $suffix;
    }

    public function search(Request $request, string $resource)
    {
        $tenant = app('tenant'); // your middleware sets this
        $cfg = $this->cfg($resource);

        $model = $cfg['model'];
        $tenantCol = $cfg['tenant_column'] ?? null;

        $idCol = $cfg['id'] ?? 'id';
        $orderBy = $cfg['order_by'] ?? $idCol;
        $searchCols = (array)($cfg['search'] ?? []);
        $where = (array)($cfg['where'] ?? []);

        $q = trim((string)$request->query('q', ''));
        $page = max(1, (int)$request->query('page', 1));
        $perPage = min(50, max(5, (int)$request->query('per_page', 10)));

        $query = $model::query();

        // tenant scope
        if (!empty($tenantCol)) {
            abort_unless($tenant, 400, 'Tenant missing');
            $query->where($tenantCol, $tenant->id);
        }

        // static where constraints (e.g. active only)
        foreach ($where as $k => $v) {
            $query->where($k, $v);
        }

        // search
        if ($q !== '' && !empty($searchCols)) {
            $query->where(function ($w) use ($searchCols, $q) {
                foreach ($searchCols as $col) {
                    $w->orWhere($col, 'like', "%{$q}%");
                }
            });
        }
        \Log::debug('select2 search', [
            'resource' => $resource,
            'model' => $model,
            'tenant_id' => $tenant?->id,
            'q' => $q,
            'order_by' => $orderBy,
            'where' => $where,
        ]);
        dd([
            'resource' => $resource,
            'tenant_id' => $tenant?->id,
            'q' => $q,
            'tenant_column' => $tenantCol,
            'where' => $where,
            'count' => (clone $query)->count(),
            'rows' => (clone $query)->orderBy($orderBy)->limit(10)->get()->toArray(),
        ]);
        $p = $query->orderBy($orderBy)
        
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $p->getCollection()->map(function ($row) use ($cfg, $idCol) {
                return [
                    'id' => $row->{$idCol},
                    'text' => $this->buildLabel($row, $cfg),
                ];
            })->values(),
            'pagination' => [
                'more' => $p->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, string $resource, $id)
    {
        $tenant = app('tenant');
        $cfg = $this->cfg($resource);

        $model = $cfg['model'];
        $tenantCol = $cfg['tenant_column'] ?? null;

        $idCol = $cfg['id'] ?? 'id';
        $where = (array)($cfg['where'] ?? []);

        $query = $model::query();

        if (!empty($tenantCol)) {
            abort_unless($tenant, 400, 'Tenant missing');
            $query->where($tenantCol, $tenant->id);
        }

        foreach ($where as $k => $v) {
            $query->where($k, $v);
        }

        $row = $query->where($idCol, $id)->firstOrFail();

        return response()->json([
            'id' => $row->{$idCol},
            'text' => $this->buildLabel($row, $cfg),
        ]);
    }
}