<?php

namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\Invoice;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


class CustomerStatementController extends Controller
{
    public function index(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');

        abort_unless(
            tenant_feature($tenant, 'statement') || tenant_feature($tenant, 'export'),
            403
        );

        $from = $request->date('from')?->startOfDay();
        $to   = $request->date('to')?->endOfDay();

        // default range: current month (nice UX)
        if (!$from && !$to) {
            $from = now()->startOfMonth();
            $to   = now()->endOfDay();
        }

        $q = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->with(['company'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id');

        if ($from) $q->whereDate('issued_at', '>=', $from->toDateString());
        if ($to)   $q->whereDate('issued_at', '<=', $to->toDateString());

        $invoices = $q->get();

        $summary = [
            'count' => $invoices->count(),
            'subtotal' => (float) $invoices->sum('subtotal'),
            'discount' => (float) $invoices->sum('discount_amount'),
            'vat'      => (float) $invoices->sum('tax_amount'),
            'total'    => (float) $invoices->sum('total'),
            'paid'     => (float) $invoices->whereNotNull('paid_at')->sum('total'),
            'unpaid'   => (float) $invoices->whereNull('paid_at')->sum('total'),
        ];

        return view('tenant.reports.statement', compact('tenant', 'invoices', 'from', 'to', 'summary'));
    }

    public function pdf(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');

        abort_unless(
            tenant_feature($tenant, 'statement') || tenant_feature($tenant, 'export'),
            403
        );

        $from = $request->date('from')?->startOfDay();
        $to   = $request->date('to')?->endOfDay();

        if (!$from && !$to) {
            $from = now()->startOfMonth();
            $to   = now()->endOfDay();
        }

        $q = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->with(['company'])
            ->orderBy('issued_at')
            ->orderBy('id');

        if ($from) $q->whereDate('issued_at', '>=', $from->toDateString());
        if ($to)   $q->whereDate('issued_at', '<=', $to->toDateString());

        $invoices = $q->get();

        $summary = [
            'count' => $invoices->count(),
            'subtotal' => (float) $invoices->sum('subtotal'),
            'discount' => (float) $invoices->sum('discount_amount'),
            'vat'      => (float) $invoices->sum('tax_amount'),
            'total'    => (float) $invoices->sum('total'),
            'paid'     => (float) $invoices->whereNotNull('paid_at')->sum('total'),
            'unpaid'   => (float) $invoices->whereNull('paid_at')->sum('total'),
        ];

        $pdf = Pdf::loadView('tenant.reports.statement_pdf', compact('tenant', 'invoices', 'from', 'to', 'summary'));
        return $pdf->stream("statement-{$tenant->subdomain}.pdf");
    }

    public function csv(Request $request, string $tenantKey): StreamedResponse
    {
        $tenant = app('tenant');

        abort_unless(
            tenant_feature($tenant, 'statement') || tenant_feature($tenant, 'export'),
            403
        );

        $from = $request->date('from')?->startOfDay();
        $to   = $request->date('to')?->endOfDay();

        if (!$from && !$to) {
            $from = now()->startOfMonth();
            $to   = now()->endOfDay();
        }

        $q = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->with(['company'])
            ->orderBy('issued_at')
            ->orderBy('id');

        if ($from) $q->whereDate('issued_at', '>=', $from->toDateString());
        if ($to)   $q->whereDate('issued_at', '<=', $to->toDateString());

        $invoices = $q->get();

        $filename = "statement-{$tenant->subdomain}.csv";

        return response()->streamDownload(function () use ($invoices) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Invoice #', 'Company', 'Issued At', 'Status', 'Total', 'Paid At']);

            foreach ($invoices as $inv) {
                fputcsv($out, [
                    $inv->invoice_number,
                    $inv->company?->name,
                    optional($inv->issued_at)->format('Y-m-d'),
                    $inv->status,
                    (string) $inv->total,
                    $inv->paid_at ? $inv->paid_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
