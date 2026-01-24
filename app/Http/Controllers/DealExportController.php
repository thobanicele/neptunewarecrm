<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Tenant;
use Illuminate\Http\Request;

class DealExportController extends Controller
{
    public function export(Request $request, Tenant $tenant)
    {
        // Example: simple CSV export (you can enhance later)
        $filename = 'deals_' . $tenant->subdomain . '_' . now()->format('Ymd_His') . '.csv';

        $deals = Deal::query()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get(['id', 'title', 'amount', 'stage_id', 'created_at']);

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($deals) {
            $out = fopen('php://output', 'w');

            // header row
            fputcsv($out, ['ID', 'Title', 'Amount', 'Stage ID', 'Created At']);

            foreach ($deals as $d) {
                fputcsv($out, [$d->id, $d->title, $d->amount, $d->stage_id, $d->created_at]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}

