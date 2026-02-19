<?php

namespace App\Http\Controllers;

use App\Mail\CompanyStatementMail;
use App\Models\Company;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;


class CompanyStatementController extends Controller
{
    public function show(Request $request, string $tenantKey, Company $company)
    {
        $tenant = app('tenant');
        $this->authorize('statement', Invoice::class);
        abort_unless((int) $company->tenant_id === (int) $tenant->id, 404);

        if (! $this->canStatement($tenant)) {
            return $this->denyStatement($company);
        }

        $data = $this->buildLedger($request, $tenant->id, $company->id);

        return view('tenant.companies.statement', array_merge($data, [
            'tenant' => $tenant,
            'company' => $company,
        ]));
    }

    public function pdf(Request $request, string $tenantKey, Company $company)
    {
        $tenant = app('tenant');
        $this->authorize('statement', Invoice::class);
        abort_unless((int) $company->tenant_id === (int) $tenant->id, 404);

        if (! $this->canStatement($tenant)) {
            return $this->denyStatement($company);
        }

        $data = $this->buildLedger($request, $tenant->id, $company->id);

        // optional "To" address snapshot (billing preferred)
        $companyAddress = $this->companyToAddressSnapshot($tenant->id, $company);

        $pdf = Pdf::loadView('tenant.companies.statement_pdf', array_merge($data, [
            'tenant' => $tenant,
            'company' => $company,
            'companyAddress' => $companyAddress,
        ]));

        return $pdf->stream("statement-{$company->id}.pdf");
    }

    public function csv(Request $request, string $tenantKey, Company $company)
    {
        $tenant = app('tenant');
        $this->authorize('statement', Invoice::class);
        abort_unless((int) $company->tenant_id === (int) $tenant->id, 404);

        if (! $this->canStatement($tenant)) {
            return $this->denyStatement($company);
        }

        $data = $this->buildLedger($request, $tenant->id, $company->id);

        $ledger = $data['ledger'];
        $filename = "statement-company-{$company->id}.csv";

        return response()->streamDownload(function () use ($ledger, $data) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Date', 'Type', 'Reference', 'Description', 'Debit', 'Credit', 'Balance']);

            // opening row
            fputcsv($out, [
                $data['from'],
                'Opening Balance',
                '',
                '',
                '',
                '',
                number_format((float)$data['opening'], 2, '.', ''),
            ]);

            foreach ($ledger as $r) {
                fputcsv($out, [
                    $r->date,
                    $r->type,
                    $r->ref,
                    $r->description,
                    number_format((float)$r->debit, 2, '.', ''),
                    number_format((float)$r->credit, 2, '.', ''),
                    number_format((float)$r->balance, 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function email(Request $request, string $tenantKey, Company $company)
    {
        $tenant = app('tenant');
        $this->authorize('statement', Invoice::class);
        abort_unless((int) $company->tenant_id === (int) $tenant->id, 404);

        // Email sending is Pro feature
        if (! tenant_feature($tenant, 'invoice_email_send')) {
            return back()->with('error', 'Email sending is not enabled for your plan.');
        }

        // Also require statement/export permission
        if (! $this->canStatement($tenant)) {
            return $this->denyStatement($company);
        }

        $toEmail = trim((string) $request->input('to')) ?: (string) ($company->email ?? '');
        if (! $toEmail) {
            return back()->with('error', 'No email address provided for this company.');
        }

        $data = $this->buildLedger($request, $tenant->id, $company->id);
        $companyAddress = $this->companyToAddressSnapshot($tenant->id, $company);

        $pdf = Pdf::loadView('tenant.companies.statement_pdf', array_merge($data, [
            'tenant' => $tenant,
            'company' => $company,
            'companyAddress' => $companyAddress,
        ]));

        Mail::to($toEmail)->send(new CompanyStatementMail(
            $tenant,
            $company,
            Carbon::parse($data['from']),
            Carbon::parse($data['to']),
            $pdf->output()
        ));

        return back()->with('success', 'Statement emailed successfully.');
    }

    /**
     * Build ledger rows + opening/closing for a company statement.
     */
    private function buildLedger(Request $request, int $tenantId, int $companyId): array
    {
        [$from, $to] = $this->resolveRange($request);
        $range = $request->get('range', 'this_month');

        // -----------------------------
        // Base subqueries (financial docs only)
        // -----------------------------
        $invBase = DB::table('invoices')
            ->selectRaw("
                issued_at as date,
                'Invoice' as type,
                COALESCE(invoice_number, CONCAT('INV #', id)) as ref,
                NULL as description,
                total as debit,
                0 as credit,
                created_at as created_at
            ")
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId);

        $payBase = DB::table('payments')
            ->selectRaw("
                paid_at as date,
                'Payment' as type,
                CONCAT('PAY #', id) as ref,
                method as description,
                0 as debit,
                amount as credit,
                created_at as created_at
            ")
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId);

        $cnBase = DB::table('credit_notes')
            ->selectRaw("
                issued_at as date,
                'Credit Note' as type,
                COALESCE(credit_note_number, CONCAT('CN #', id)) as ref,
                reason as description,
                0 as debit,
                amount as credit,
                created_at as created_at
            ")
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId);

        $rfBase = DB::table('credit_note_refunds')
            ->selectRaw("
                refunded_at as date,
                'Refund' as type,
                CONCAT('RF #', id) as ref,
                method as description,
                amount as debit,
                0 as credit,
                created_at as created_at
            ")
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId);

        // -----------------------------
        // Opening balance: everything BEFORE from-date
        // -----------------------------
        $openingRows = (clone $invBase)->whereDate('issued_at', '<', $from)
            ->unionAll((clone $payBase)->whereDate('paid_at', '<', $from))
            ->unionAll((clone $cnBase)->whereDate('issued_at', '<', $from))
            ->unionAll((clone $rfBase)->whereDate('refunded_at', '<', $from));

        $opening = (float) (DB::query()
            ->fromSub($openingRows, 'o')
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as balance')
            ->value('balance') ?? 0);

        // -----------------------------
        // Ledger rows within range
        // -----------------------------
        $rows = (clone $invBase)->whereDate('issued_at', '>=', $from)->whereDate('issued_at', '<=', $to)
            ->unionAll((clone $payBase)->whereDate('paid_at', '>=', $from)->whereDate('paid_at', '<=', $to))
            ->unionAll((clone $cnBase)->whereDate('issued_at', '>=', $from)->whereDate('issued_at', '<=', $to))
            ->unionAll((clone $rfBase)->whereDate('refunded_at', '>=', $from)->whereDate('refunded_at', '<=', $to));

        $ledger = DB::query()
            ->fromSub($rows, 'x')
            ->whereNotNull('date') // defensive
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        // Running balance
        $running = $opening;
        $ledger = $ledger->map(function ($r) use (&$running) {
            $running += ((float) $r->debit) - ((float) $r->credit);
            $r->balance = $running;
            return $r;
        });

        $periodDebit  = (float) $ledger->sum('debit');
        $periodCredit = (float) $ledger->sum('credit');
        $closing      = (float) $running;

        return [
            'ledger'       => $ledger,
            'from'         => $from,
            'to'           => $to,
            'opening'      => $opening,
            'periodDebit'  => $periodDebit,
            'periodCredit' => $periodCredit,
            'closing'      => $closing,
            'range'        => $range,
        ];
    }

    private function resolveRange(Request $request): array
    {
        $range = $request->get('range', 'this_month');
        $tz = config('app.timezone');

        $today = Carbon::now($tz)->startOfDay();

        $from = $today->copy()->startOfMonth();
        $to   = $today->copy()->endOfMonth();

        switch ($range) {
            case 'today':
                $from = $today->copy();
                $to   = $today->copy();
                break;

            case 'yesterday':
                $from = $today->copy()->subDay();
                $to   = $from->copy();
                break;

            case 'this_week':
                $from = $today->copy()->startOfWeek();
                $to   = $today->copy()->endOfWeek();
                break;

            case 'previous_week':
                $from = $today->copy()->subWeek()->startOfWeek();
                $to   = $today->copy()->subWeek()->endOfWeek();
                break;

            case 'this_month':
                $from = $today->copy()->startOfMonth();
                $to   = $today->copy()->endOfMonth();
                break;

            case 'previous_month':
                $from = $today->copy()->subMonthNoOverflow()->startOfMonth();
                $to   = $today->copy()->subMonthNoOverflow()->endOfMonth();
                break;

            case 'this_quarter':
                $from = $today->copy()->firstOfQuarter();
                $to   = $today->copy()->lastOfQuarter();
                break;

            case 'previous_quarter':
                $from = $today->copy()->subQuarter()->firstOfQuarter();
                $to   = $today->copy()->subQuarter()->lastOfQuarter();
                break;

            case 'this_year':
                $from = $today->copy()->startOfYear();
                $to   = $today->copy()->endOfYear();
                break;

            case 'previous_year':
                $from = $today->copy()->subYear()->startOfYear();
                $to   = $today->copy()->subYear()->endOfYear();
                break;

            case 'custom':
                $from = Carbon::parse($request->get('from') ?: $today->toDateString(), $tz)->startOfDay();
                $to   = Carbon::parse($request->get('to') ?: $today->toDateString(), $tz)->endOfDay();
                break;
        }

        return [$from->toDateString(), $to->toDateString()];
    }

    // -------------------------------------------------------------------------
    // Feature gates (unchanged)
    // -------------------------------------------------------------------------
    private function canStatement($tenant): bool
    {
        return tenant_feature($tenant, 'statement') || tenant_feature($tenant, 'export');
    }

    private function denyStatement(Company $company)
    {
        return redirect()
            ->to(tenant_route('tenant.companies.show', $company))
            ->with('error', 'Statements are not enabled for your plan.');
    }

    // -------------------------------------------------------------------------
    // Company "To" address snapshot for PDF
    // -------------------------------------------------------------------------
    private function companyToAddressSnapshot(int $tenantId, Company $company): string
    {
        // Prefer billing default; fallback shipping; fallback blank.
        // Works with your addresses relation already used elsewhere.
        try {
            $company->loadMissing(['addresses.country', 'addresses.subdivision']);
            $billing = $company->addresses
                ->where('type', 'billing')
                ->sortByDesc('is_default_billing')
                ->sortByDesc('id')
                ->first();

            $shipping = $company->addresses
                ->where('type', 'shipping')
                ->sortByDesc('is_default_shipping')
                ->sortByDesc('id')
                ->first();

            $addr = $billing?->toSnapshotString() ?: $shipping?->toSnapshotString();
            return (string) ($addr ?: '');
        } catch (\Throwable $e) {
            return '';
        }
    }
}



