<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CreditNote;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\TransactionAllocation;
use App\Models\CreditNoteRefund;
use App\Models\Payment;
use App\Services\InvoicePaymentStatusService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');
        $this->authorize('viewAny', Payment::class);

        $q = trim((string) $request->query('q', ''));

        // filters
        $company_id = (string) $request->query('company_id', '');
        $method     = (string) $request->query('method', '');
        $state      = (string) $request->query('state', ''); // allocated|unallocated|''

        // sorting
        $sort = (string) $request->query('sort', 'paid_at');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'paid_at','company','method','reference','amount','allocated','unallocated','created_at','updated_at'
        ];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'paid_at';

        // allocations per payment (sum amount_applied where payment_id)
        $allocSub = DB::table('transaction_allocations')
            ->select('payment_id', DB::raw('SUM(amount_applied) as allocated_total'))
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('payment_id')
            ->groupBy('payment_id');

        $query = Payment::query()
            ->where('payments.tenant_id', $tenant->id)
            ->with(['company'])
            ->leftJoinSub($allocSub, 'alloc', function ($join) {
                $join->on('alloc.payment_id', '=', 'payments.id');
            })
            ->select([
                'payments.*',
                DB::raw('COALESCE(alloc.allocated_total, 0) as allocated_total'),
                DB::raw('(payments.amount - COALESCE(alloc.allocated_total, 0)) as unallocated_total'),
            ])
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($x) use ($q) {
                    $x->where('payments.reference', 'like', "%{$q}%")
                    ->orWhere('payments.method', 'like', "%{$q}%")
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($company_id !== '', fn ($qry) => $qry->where('payments.company_id', (int) $company_id))
            ->when($method !== '', fn ($qry) => $qry->where('payments.method', $method))
            ->when($state !== '', function ($qry) use ($state) {
                $tol = 0.01;

                if ($state === 'allocated') {
                    $qry->whereRaw('(payments.amount - COALESCE(alloc.allocated_total, 0)) <= ?', [$tol]);
                }

                if ($state === 'unallocated') {
                    $qry->whereRaw('(payments.amount - COALESCE(alloc.allocated_total, 0)) > ?', [$tol]);
                }
            });

        // sorting
        if ($sort === 'company') {
            $query->leftJoin('companies', function ($join) use ($tenant) {
                $join->on('companies.id', '=', 'payments.company_id')
                    ->where('companies.tenant_id', '=', $tenant->id);
            })
            ->addSelect(DB::raw('companies.name as company_sort'))
            ->orderBy('company_sort', $dir);
        } elseif ($sort === 'allocated') {
            $query->orderBy('allocated_total', $dir);
        } elseif ($sort === 'unallocated') {
            $query->orderBy('unallocated_total', $dir);
        } else {
            $query->orderBy("payments.$sort", $dir);
        }

        $payments = $query
            ->orderByDesc('payments.id')
            ->paginate(20)
            ->withQueryString();

        // filter dropdown options
        $companies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id','name']);

        $methods = Payment::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('method')
            ->where('method', '<>', '')
            ->distinct()
            ->orderBy('method')
            ->pluck('method');

        $canExport = tenant_feature($tenant, 'export');

        return view('tenant.payments.index', compact(
            'tenant',
            'payments',
            'companies',
            'methods',
            'q',
            'company_id',
            'method',
            'state',
            'sort',
            'dir',
            'canExport'
        ));
    }

    public function openInvoices(Request $request, string $tenantKey, Company $company)
    {
        $tenant = app('tenant');
        $this->authorize('create', Payment::class);
        abort_unless((int) $company->tenant_id === (int) $tenant->id, 404);

        $allocSub = DB::table('transaction_allocations')
            ->select('invoice_id', DB::raw('SUM(amount_applied) as allocated'))
            ->where('tenant_id', $tenant->id)
            ->groupBy('invoice_id');

        $rows = Invoice::query()
            ->where('invoices.tenant_id', $tenant->id)
            ->where('invoices.company_id', $company->id)
            ->leftJoinSub($allocSub, 'alloc', function ($join) {
                $join->on('alloc.invoice_id', '=', 'invoices.id');
            })
            ->select([
                'invoices.id',
                'invoices.invoice_number',
                'invoices.issued_at',
                'invoices.total',
                DB::raw('COALESCE(alloc.allocated, 0) as allocated_total'),
                DB::raw('(invoices.total - COALESCE(alloc.allocated, 0)) as outstanding'),
            ])
            ->whereRaw('(invoices.total - COALESCE(alloc.allocated, 0)) > 0.009')
            ->orderBy('invoices.issued_at')
            ->orderBy('invoices.id')
            ->get();

        return response()->json([
            'invoices' => $rows->map(fn ($i) => [
                'id' => $i->id,
                'invoice_number' => $i->invoice_number,
                'issued_at' => optional($i->issued_at)->format('Y-m-d') ?: $i->issued_at,
                'total' => (float) $i->total,
                'outstanding' => (float) $i->outstanding,
            ])->values(),
        ]);
    }


    public function create(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');
        $this->authorize('create', Payment::class);

        $prefillCompanyId = $request->integer('company_id') ?: null;
        $prefillContactId = $request->integer('contact_id') ?: null;
        $prefillInvoiceId = $request->integer('invoice_id') ?: null;

        $companies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id','name']);

        $contacts = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id','name']);

        // Sum allocations per invoice (payments + credit notes)
        $allocSub = DB::table('transaction_allocations')
            ->select('invoice_id', DB::raw('SUM(amount_applied) as allocated'))
            ->where('tenant_id', $tenant->id)
            ->groupBy('invoice_id');

        // Only show invoices that still have outstanding balance
        $openInvoices = Invoice::query()
            ->where('invoices.tenant_id', $tenant->id)
            ->when($prefillCompanyId, fn($q) => $q->where('invoices.company_id', $prefillCompanyId))
            ->leftJoinSub($allocSub, 'alloc', function ($join) {
                $join->on('alloc.invoice_id', '=', 'invoices.id');
            })
            ->select([
                'invoices.id',
                'invoices.company_id',
                'invoices.invoice_number',
                'invoices.issued_at',
                'invoices.total',
                DB::raw('COALESCE(alloc.allocated, 0) as allocated_total'),
                DB::raw('(invoices.total - COALESCE(alloc.allocated, 0)) as outstanding'),
            ])
            ->whereRaw('(invoices.total - COALESCE(alloc.allocated, 0)) > 0.009') // outstanding > 0
            ->orderBy('invoices.issued_at')
            ->orderBy('invoices.id')
            ->get();

        return view('tenant.payments.create', compact(
            'tenant','companies','contacts','openInvoices',
            'prefillCompanyId','prefillContactId','prefillInvoiceId'
        ));
    }


    public function store(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');
        $this->authorize('create', Payment::class);

        $data = $request->validate([
            'company_id' => ['required','integer'],
            'contact_id' => ['nullable','integer'],
            'paid_at' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'method' => ['nullable','string','max:50'],
            'reference' => ['nullable','string','max:120'],
            'notes' => ['nullable','string'],
            'apply_invoice_id' => ['nullable','integer'],
        ]);

        // ✅ If an invoice is selected, hard-guard it belongs to this tenant AND the selected company
        $invoiceId = $data['apply_invoice_id'] ?? null;

        if ($invoiceId) {
            abort_unless(
                Invoice::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('company_id', $data['company_id'])
                    ->where('id', $invoiceId)
                    ->exists(),
                404
            );
        }

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'company_id' => $data['company_id'],
            'contact_id' => $data['contact_id'] ?? null,

            // ✅ store invoice_id (since your table already has it)
            'invoice_id' => $invoiceId,

            'paid_at' => $data['paid_at'],
            'amount' => $data['amount'],
            'method' => $data['method'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by_user_id' => auth()->id(),
        ]);

        // Auto allocate (still uses apply_invoice_id as the "priority" invoice)
        $this->autoAllocateToInvoices(
            tenantId: $tenant->id,
            companyId: (int) $data['company_id'],
            amount: (float) $data['amount'],
            applyInvoiceId: $invoiceId,
            paymentId: $payment->id,
            creditNoteId: null,
            appliedAt: $data['paid_at']
        );

        $invoiceIds = DB::table('transaction_allocations')
            ->where('tenant_id', $tenant->id)
            ->where('payment_id', $payment->id)
            ->pluck('invoice_id')
            ->unique()
            ->values()
            ->all();

        app(InvoicePaymentStatusService::class)->syncMany($tenant->id, $invoiceIds);

        return redirect()
            ->route('tenant.payments.show', ['tenant' => $tenantKey, 'payment' => $payment->id])
            ->with('success', 'Payment captured and allocated.');
    }


    public function show(string $tenantKey, Payment $payment)
    {
        $tenant = app('tenant');
        $this->authorize('view', $payment);
        abort_unless((int) $payment->tenant_id === (int) $tenant->id, 404);

        $payment->load('company','contact');

        $allocations = TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('payment_id', $payment->id)
            ->with(['invoice:id,tenant_id,invoice_number,issued_at,total'])
            ->orderBy('applied_at')
            ->orderBy('id')
            ->get();


        $allocatedTotal = (float) $allocations->sum('amount_applied');
        $unallocated = round(max(0, (float)$payment->amount - $allocatedTotal), 2);

        return view('tenant.payments.show', compact(
            'tenant','payment','allocations','allocatedTotal','unallocated'
        ));
    }


    private function autoAllocateToInvoices(
        int $tenantId,
        int $companyId,
        float $amount,
        ?int $applyInvoiceId,
        ?int $paymentId = null,
        ?int $creditNoteId = null,
        ?string $appliedAt = null
    ): float {
        $remaining = round($amount, 2);
        if ($remaining <= 0) return 0;

        $appliedAt = $appliedAt ?: now()->toDateString();

        // Build invoice list: specific first if chosen, else oldest outstanding
        $invoiceQuery = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->orderBy('issued_at')
            ->orderBy('id');

        if ($applyInvoiceId) {
            $invoiceQuery->where('id', $applyInvoiceId);
        }

        $invoices = $invoiceQuery->get(['id','total','issued_at']);

        if ($invoices->isEmpty()) return $remaining;

        // Preload total applied per invoice
        $appliedMap = DB::table('transaction_allocations')
            ->select('invoice_id', DB::raw('SUM(amount_applied) as applied'))
            ->where('tenant_id', $tenantId)
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->groupBy('invoice_id')
            ->pluck('applied', 'invoice_id');

        foreach ($invoices as $inv) {
            if ($remaining <= 0) break;

            $alreadyApplied = (float) ($appliedMap[$inv->id] ?? 0);
            $outstanding = round(((float) $inv->total) - $alreadyApplied, 2);

            if ($outstanding <= 0) continue;

            $use = min($outstanding, $remaining);
            $use = round($use, 2);

            TransactionAllocation::create([
                'tenant_id' => $tenantId,
                'invoice_id' => $inv->id,
                'payment_id' => $paymentId,
                'credit_note_id' => $creditNoteId,
                'amount_applied' => $use,
                'applied_at' => $appliedAt,
            ]);

            $remaining = round($remaining - $use, 2);
        }

        return $remaining; // any unallocated remainder
    }

    public function allocateForm(string $tenantKey, Payment $payment)
    {
        $tenant = app('tenant');
        $this->authorize('update', $payment);
        abort_unless((int) $payment->tenant_id === (int) $tenant->id, 404);

        $payment->load('company','contact');

        $allocated = (float) TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('payment_id', $payment->id)
            ->sum('amount_applied');

        $unallocated = round(max(0, (float) $payment->amount - $allocated), 2);

        $invoices = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('company_id', $payment->company_id)
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get(['id','invoice_number','issued_at','total']);

        // total applied per invoice from ALL sources (payments + credit notes)
        $appliedMap = TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->selectRaw('invoice_id, SUM(amount_applied) as applied')
            ->groupBy('invoice_id')
            ->pluck('applied', 'invoice_id');

        $rows = $invoices->map(function ($inv) use ($appliedMap) {
            $applied = (float) ($appliedMap[$inv->id] ?? 0);
            $outstanding = round(((float)$inv->total) - $applied, 2);

            return (object)[
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number ?: ('INV-'.$inv->id),
                'issued_at' => $inv->issued_at,
                'total' => (float) $inv->total,
                'applied_total' => $applied,
                'outstanding' => max(0, $outstanding),
            ];
        });

        return view('tenant.payments.allocate', compact('tenant','payment','rows','allocated','unallocated'));
    }

    public function allocateStore(Request $request, string $tenantKey, Payment $payment)
    {
        $tenant = app('tenant');
        $this->authorize('update', $payment);
        abort_unless((int) $payment->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'applied_at' => ['nullable','date'],
            'allocations' => ['required','array','min:1'],
            'allocations.*.invoice_id' => ['required','integer'],
            'allocations.*.amount' => ['required','numeric','min:0'],
        ]);

        $appliedAt = $data['applied_at'] ?: ($payment->paid_at?->toDateString() ?? now()->toDateString());

        return DB::transaction(function () use ($tenant, $tenantKey, $payment, $data, $appliedAt) {

            // lock payment row to avoid race
            $paymentLocked = Payment::query()
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $alreadyAllocated = (float) TransactionAllocation::query()
                ->where('tenant_id', $tenant->id)
                ->where('payment_id', $paymentLocked->id)
                ->sum('amount_applied');

            $unallocated = round(max(0, (float)$paymentLocked->amount - $alreadyAllocated), 2);

            $invoiceIds = collect($data['allocations'])->pluck('invoice_id')->unique()->values();

            $invoices = Invoice::query()
                ->where('tenant_id', $tenant->id)
                ->where('company_id', $paymentLocked->company_id)
                ->whereIn('id', $invoiceIds)
                ->lockForUpdate()
                ->get(['id','total','invoice_number']);

            $invoiceMap = $invoices->keyBy('id');

            $appliedMap = TransactionAllocation::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('invoice_id', $invoiceIds)
                ->selectRaw('invoice_id, SUM(amount_applied) as applied')
                ->groupBy('invoice_id')
                ->pluck('applied', 'invoice_id');

            $toInsert = [];
            $sumToApply = 0.0;

            foreach ($data['allocations'] as $row) {
                $invoiceId = (int) $row['invoice_id'];
                $amount = round((float) $row['amount'], 2);

                if ($amount <= 0) continue;

                $inv = $invoiceMap[$invoiceId] ?? null;
                if (!$inv) {
                    throw ValidationException::withMessages([
                        'allocations' => ["Invoice #{$invoiceId} is not valid for this customer."],
                    ]);
                }

                $already = (float) ($appliedMap[$invoiceId] ?? 0);
                $outstanding = max(0, round(((float)$inv->total) - $already, 2));

                if ($amount > $outstanding) {
                    throw ValidationException::withMessages([
                        'allocations' => ["Amount for {$inv->invoice_number} exceeds outstanding (R " . number_format($outstanding,2) . ")."],
                    ]);
                }

                $sumToApply += $amount;

                $toInsert[] = [
                    'tenant_id' => $tenant->id,
                    'invoice_id' => $invoiceId,
                    'payment_id' => $paymentLocked->id,
                    'credit_note_id' => null,
                    'amount_applied' => $amount,
                    'applied_at' => $appliedAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $sumToApply = round($sumToApply, 2);

            if ($sumToApply <= 0) {
                throw ValidationException::withMessages([
                    'allocations' => ['Please enter at least one amount to apply.'],
                ]);
            }

            if ($sumToApply > $unallocated) {
                throw ValidationException::withMessages([
                    'allocations' => ["Total applied (R " . number_format($sumToApply,2) . ") exceeds unallocated (R " . number_format($unallocated,2) . ")."],
                ]);
            }

            TransactionAllocation::insert($toInsert);
                app(\App\Services\InvoicePaymentStatusService::class)
                ->syncMany($tenant->id, $invoiceIds->all());

            return redirect()
                ->route('tenant.payments.show', ['tenant' => $tenantKey, 'payment' => $paymentLocked->id])
                ->with('success', 'Payment allocations saved.');
        });
    }

    public function allocationDestroy(string $tenantKey, Payment $payment, TransactionAllocation $allocation)
    {
        $tenant = app('tenant');
        $this->authorize('update', $payment);
        abort_unless((int)$payment->tenant_id === (int)$tenant->id, 404);

        // Ensure allocation belongs to this payment + tenant
        abort_unless((int)$allocation->tenant_id === (int)$tenant->id, 404);
        abort_unless((int)$allocation->payment_id === (int)$payment->id, 404);

        $invoiceId = (int) $allocation->invoice_id;
        $allocation->delete();
        app(\App\Services\InvoicePaymentStatusService::class)
            ->syncMany($tenant->id, [$invoiceId]);

        return redirect()
            ->route('tenant.payments.show', ['tenant' => $tenantKey, 'payment' => $payment->id])
            ->with('success', 'Allocation removed.');
    }

    public function allocationsReset(string $tenantKey, Payment $payment)
    {
        $tenant = app('tenant');
        $this->authorize('update', $payment);
        abort_unless((int)$payment->tenant_id === (int)$tenant->id, 404);

        $invoiceIds = TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('payment_id', $payment->id)
            ->pluck('invoice_id')
            ->unique()
            ->values()
            ->all();

        TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('payment_id', $payment->id)
            ->delete();

        app(\App\Services\InvoicePaymentStatusService::class)
            ->syncMany($tenant->id, $invoiceIds);

        return redirect()
            ->route('tenant.payments.show', ['tenant' => $tenantKey, 'payment' => $payment->id])
            ->with('success', 'All allocations removed. Payment is now unallocated.');
    }

    public function export(Request $request, string $tenantKey): StreamedResponse
    {
        $tenant = app('tenant');
        abort_unless(auth()->user()->can('export.run'), 403);

        if (!tenant_feature($tenant, 'export')) {
            return back()->with('error', 'Export to Excel is available on the Premium plan.');
        }

        $q = trim((string) $request->query('q', ''));
        $company_id = (string) $request->query('company_id', '');
        $method = (string) $request->query('method', '');
        $state = (string) $request->query('state', '');

        $sort = (string) $request->query('sort', 'paid_at');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'paid_at','company','method','reference','amount','allocated','unallocated','created_at','updated_at'
        ];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'paid_at';

        $allocSub = DB::table('transaction_allocations')
            ->select('payment_id', DB::raw('SUM(amount_applied) as allocated_total'))
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('payment_id')
            ->groupBy('payment_id');

        $query = Payment::query()
            ->where('payments.tenant_id', $tenant->id)
            ->with(['company'])
            ->leftJoinSub($allocSub, 'alloc', fn($j) => $j->on('alloc.payment_id', '=', 'payments.id'))
            ->select([
                'payments.*',
                DB::raw('COALESCE(alloc.allocated_total, 0) as allocated_total'),
                DB::raw('(payments.amount - COALESCE(alloc.allocated_total, 0)) as unallocated_total'),
            ])
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($x) use ($q) {
                    $x->where('payments.reference', 'like', "%{$q}%")
                    ->orWhere('payments.method', 'like', "%{$q}%")
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($company_id !== '', fn ($qry) => $qry->where('payments.company_id', (int) $company_id))
            ->when($method !== '', fn ($qry) => $qry->where('payments.method', $method))
            ->when($state !== '', function ($qry) use ($state) {
                $tol = 0.01;
                if ($state === 'allocated') {
                    $qry->whereRaw('(payments.amount - COALESCE(alloc.allocated_total, 0)) <= ?', [$tol]);
                }
                if ($state === 'unallocated') {
                    $qry->whereRaw('(payments.amount - COALESCE(alloc.allocated_total, 0)) > ?', [$tol]);
                }
            });

        if ($sort === 'company') {
            $query->leftJoin('companies', function ($join) use ($tenant) {
                $join->on('companies.id', '=', 'payments.company_id')
                    ->where('companies.tenant_id', '=', $tenant->id);
            })
            ->addSelect(DB::raw('companies.name as company_sort'))
            ->orderBy('company_sort', $dir);
        } elseif ($sort === 'allocated') {
            $query->orderBy('allocated_total', $dir);
        } elseif ($sort === 'unallocated') {
            $query->orderBy('unallocated_total', $dir);
        } else {
            $query->orderBy("payments.$sort", $dir);
        }

        $rows = $query->orderByDesc('payments.id')->get();

        $filename = 'payments-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date','Customer','Method','Reference','Amount','Allocated','Unallocated','Linked Invoice ID']);

            foreach ($rows as $p) {
                fputcsv($out, [
                    optional($p->paid_at)->format('Y-m-d') ?? '',
                    $p->company?->name ?? '',
                    $p->method ?? '',
                    $p->reference ?? '',
                    number_format((float)$p->amount, 2, '.', ''),
                    number_format((float)($p->allocated_total ?? 0), 2, '.', ''),
                    number_format((float)max(0, (float)($p->unallocated_total ?? 0)), 2, '.', ''),
                    $p->invoice_id ?? '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }


}