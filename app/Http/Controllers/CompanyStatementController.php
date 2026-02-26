<?php

namespace App\Http\Controllers;

use App\Mail\CompanyStatementMail;
use App\Models\Company;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CompanyStatementController extends Controller
{
    protected function resolveLocalLogoPath($tenant): ?string
    {
        if (empty($tenant?->logo_path)) {
            return null;
        }

        $disk = (string) config('filesystems.tenant_logo_disk', 'tenant_logos');

        try {
            $bytes = Storage::disk($disk)->get($tenant->logo_path);
            if (!$bytes) return null;

            // Guard against huge uploads killing DomPDF
            if (strlen($bytes) > 2 * 1024 * 1024) { // 2MB
                Log::warning('Statement PDF logo too large, skipping', [
                    'tenant_id' => $tenant->id,
                    'path' => $tenant->logo_path,
                    'size' => strlen($bytes),
                ]);
                return null;
            }

            $tmpDir = storage_path('app/tmp');
            if (!is_dir($tmpDir)) {
                @mkdir($tmpDir, 0775, true);
            }
            if (!is_writable($tmpDir)) {
                Log::warning('Statement PDF tmp dir not writable', ['tmp' => $tmpDir]);
                return null;
            }

            $ext = pathinfo($tenant->logo_path, PATHINFO_EXTENSION) ?: 'png';
            $localPath = $tmpDir . '/tenant_logo_' . $tenant->id . '_' . uniqid('', true) . '.' . $ext;

            file_put_contents($localPath, $bytes);

            return $localPath;
        } catch (\Throwable $e) {
            Log::warning('Statement PDF logo fetch failed: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'disk' => $disk,
                'path' => $tenant->logo_path,
                'class' => get_class($e),
                'prev' => $e->getPrevious()?->getMessage(),
            ]);

            return null;
        }
    }

    public function show(Request $request, string $tenantKey, Company $company)
    {
        $tenant = app('tenant');
        $this->authorize('statement', Invoice::class);
        abort_unless((int) $company->tenant_id === (int) $tenant->id, 404);

        if (!$this->canStatement($tenant)) {
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
        @ini_set('memory_limit', '768M');
        @set_time_limit(180);

        $tenant = app('tenant');
        $this->authorize('statement', Invoice::class);
        abort_unless((int) $company->tenant_id === (int) $tenant->id, 404);

        if (!$this->canStatement($tenant)) {
            return $this->denyStatement($company);
        }

        $data = $this->buildLedger($request, $tenant->id, $company->id);
        $companyAddress = $this->companyToAddressSnapshot($tenant->id, $company);

        $pdfLogoPath = $this->resolveLocalLogoPath($tenant);

        try {
            Log::info('Statement PDF render start', ['company_id' => $company->id, 'tenant_id' => $tenant->id]);

            $pdf = Pdf::loadView('tenant.companies.statement_pdf', array_merge($data, [
                'tenant' => $tenant,
                'company' => $company,
                'companyAddress' => $companyAddress,
                'pdfLogoPath' => $pdfLogoPath,
            ]))->setPaper('a4');

            $bytes = $pdf->output();

            Log::info('Statement PDF render done', [
                'company_id' => $company->id,
                'bytes' => strlen($bytes),
                'logo_used' => (bool) $pdfLogoPath,
            ]);

            $filename = "statement-{$company->id}.pdf";

            return response($bytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Statement PDF render failed: ' . $e->getMessage(), [
                'company_id' => $company->id,
                'tenant_id' => $tenant->id,
                'class' => get_class($e),
                'prev' => $e->getPrevious()?->getMessage(),
            ]);

            abort(500, 'PDF generation failed.');
        } finally {
            if ($pdfLogoPath && file_exists($pdfLogoPath)) {
                @unlink($pdfLogoPath);
            }
        }
    }

    public function csv(Request $request, string $tenantKey, Company $company)
    {
        $tenant = app('tenant');
        $this->authorize('statement', Invoice::class);
        abort_unless((int) $company->tenant_id === (int) $tenant->id, 404);

        if (!$this->canStatement($tenant)) {
            return $this->denyStatement($company);
        }

        $data = $this->buildLedger($request, $tenant->id, $company->id);

        $ledger = $data['ledger'];
        $filename = "statement-company-{$company->id}.csv";

        return response()->streamDownload(function () use ($ledger, $data) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Date', 'Type', 'Reference', 'Description', 'Debit', 'Credit', 'Balance']);

            fputcsv($out, [
                $data['from'],
                'Opening Balance',
                '',
                '',
                '',
                '',
                number_format((float) $data['opening'], 2, '.', ''),
            ]);

            foreach ($ledger as $r) {
                fputcsv($out, [
                    $r->date,
                    $r->type,
                    $r->ref,
                    $r->description,
                    number_format((float) $r->debit, 2, '.', ''),
                    number_format((float) $r->credit, 2, '.', ''),
                    number_format((float) $r->balance, 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function email(Request $request, string $tenantKey, Company $company)
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(180);

        $tenant = app('tenant');
        $this->authorize('statement', Invoice::class);
        abort_unless((int) $company->tenant_id === (int) $tenant->id, 404);

        if (!tenant_feature($tenant, 'invoice_email_send')) {
            return back()->with('error', 'Email sending is not enabled for your plan.');
        }

        if (!$this->canStatement($tenant)) {
            return $this->denyStatement($company);
        }

        $toEmail = trim((string) $request->input('to')) ?: (string) ($company->email ?? '');
        if (!$toEmail) {
            return back()->with('error', 'No email address provided for this company.');
        }

        $data = $this->buildLedger($request, $tenant->id, $company->id);
        $companyAddress = $this->companyToAddressSnapshot($tenant->id, $company);

        $pdfLogoPath = $this->resolveLocalLogoPath($tenant);

        try {
            $pdf = Pdf::loadView('tenant.companies.statement_pdf', array_merge($data, [
                'tenant' => $tenant,
                'company' => $company,
                'companyAddress' => $companyAddress,
                'pdfLogoPath' => $pdfLogoPath,
            ]))->setPaper('a4');

            $bytes = $pdf->output();

            Mail::to($toEmail)->send(new CompanyStatementMail(
                $tenant,
                $company,
                Carbon::parse($data['from']),
                Carbon::parse($data['to']),
                $bytes
            ));

            return back()->with('success', 'Statement emailed successfully.');
        } finally {
            if ($pdfLogoPath && file_exists($pdfLogoPath)) {
                @unlink($pdfLogoPath);
            }
        }
    }

    /**
     * Build ledger rows + opening/closing for a company statement.
     */
    private function buildLedger(Request $request, int $tenantId, int $companyId): array
    {
        [$from, $to] = $this->resolveRange($request);
        $range = $request->get('range', 'this_month');

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

        $openingRows = (clone $invBase)->whereDate('issued_at', '<', $from)
            ->unionAll((clone $payBase)->whereDate('paid_at', '<', $from))
            ->unionAll((clone $cnBase)->whereDate('issued_at', '<', $from))
            ->unionAll((clone $rfBase)->whereDate('refunded_at', '<', $from));

        $opening = (float) (DB::query()
            ->fromSub($openingRows, 'o')
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as balance')
            ->value('balance') ?? 0);

        $rows = (clone $invBase)->whereDate('issued_at', '>=', $from)->whereDate('issued_at', '<=', $to)
            ->unionAll((clone $payBase)->whereDate('paid_at', '>=', $from)->whereDate('paid_at', '<=', $to))
            ->unionAll((clone $cnBase)->whereDate('issued_at', '>=', $from)->whereDate('issued_at', '<=', $to))
            ->unionAll((clone $rfBase)->whereDate('refunded_at', '>=', $from)->whereDate('refunded_at', '<=', $to));

        $ledger = DB::query()
            ->fromSub($rows, 'x')
            ->whereNotNull('date')
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

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
            'ledger' => $ledger,
            'from' => $from,
            'to' => $to,
            'opening' => $opening,
            'periodDebit' => $periodDebit,
            'periodCredit' => $periodCredit,
            'closing' => $closing,
            'range' => $range,
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

    private function companyToAddressSnapshot(int $tenantId, Company $company): string
    {
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