<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\TransactionAllocation;
use App\Models\CreditNote;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\TaxType;
use App\Models\CreditNoteItem;
use App\Services\DocumentNumberService;
use Symfony\Component\HttpFoundation\StreamedResponse;



class CreditNoteController extends Controller
{
    public function index(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');
        $this->authorize('viewAny', CreditNote::class);
        $canExport = tenant_feature($tenant, 'export');


        $q = trim((string) $request->query('q', ''));

        // filters
        $company_id = (string) $request->query('company_id', '');
        $state      = (string) $request->query('state', ''); // available|allocated|refunded|''

        // sorting
        $sort = (string) $request->query('sort', 'issued_at');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'issued_at',
            'credit_note_number',
            'company',
            'amount',
            'allocated',
            'refunded',
            'available',
            'created_at',
            'updated_at',
        ];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'issued_at';

        // subquery: total allocated per credit note
        $allocSub = DB::table('transaction_allocations')
            ->select('credit_note_id', DB::raw('SUM(amount_applied) as allocated_total'))
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('credit_note_id')
            ->groupBy('credit_note_id');

        // subquery: total refunded per credit note
        // NOTE: adjust table/column names if yours differ
        $refundSub = DB::table('credit_note_refunds')
            ->select('credit_note_id', DB::raw('SUM(amount) as refunded_total'))
            ->where('tenant_id', $tenant->id)
            ->groupBy('credit_note_id');

        $query = CreditNote::query()
            ->where('credit_notes.tenant_id', $tenant->id)
            ->with(['company'])
            ->leftJoinSub($allocSub, 'alloc', function ($join) {
                $join->on('alloc.credit_note_id', '=', 'credit_notes.id');
            })
            ->leftJoinSub($refundSub, 'ref', function ($join) {
                $join->on('ref.credit_note_id', '=', 'credit_notes.id');
            })
            ->select([
                'credit_notes.*',
                DB::raw('COALESCE(alloc.allocated_total, 0) as allocated_total'),
                DB::raw('COALESCE(ref.refunded_total, 0) as refunded_total'),
                DB::raw('(credit_notes.amount - COALESCE(alloc.allocated_total, 0) - COALESCE(ref.refunded_total, 0)) as available_total'),
            ])
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($x) use ($q) {
                    $x->where('credit_notes.credit_note_number', 'like', "%{$q}%")
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($company_id !== '', fn ($qry) => $qry->where('credit_notes.company_id', (int) $company_id))
            ->when($state !== '', function ($qry) use ($state) {
                // tolerance to avoid 0.01 rounding leftovers
                $tol = 0.01;

                if ($state === 'available') {
                    $qry->whereRaw('(credit_notes.amount - COALESCE(alloc.allocated_total, 0) - COALESCE(ref.refunded_total, 0)) > ?', [$tol]);
                }

                if ($state === 'allocated') {
                    // fully allocated: available ~ 0 AND refunded ~ 0
                    $qry->whereRaw('ABS((credit_notes.amount - COALESCE(alloc.allocated_total, 0) - COALESCE(ref.refunded_total, 0))) <= ?', [$tol])
                        ->whereRaw('COALESCE(ref.refunded_total, 0) <= ?', [$tol]);
                }

                if ($state === 'refunded') {
                    // has any refunds
                    $qry->whereRaw('COALESCE(ref.refunded_total, 0) > ?', [$tol]);
                }
            });

        // sorting
        if ($sort === 'company') {
            $query->leftJoin('companies', function ($join) use ($tenant) {
                $join->on('companies.id', '=', 'credit_notes.company_id')
                    ->where('companies.tenant_id', '=', $tenant->id);
            })
            ->addSelect(DB::raw('companies.name as company_sort'))
            ->orderBy('company_sort', $dir);
        } elseif (in_array($sort, ['allocated','refunded','available'], true)) {
            $map = [
                'allocated'  => 'allocated_total',
                'refunded'   => 'refunded_total',
                'available'  => 'available_total',
            ];
            $query->orderBy($map[$sort], $dir);
        } else {
            $query->orderBy("credit_notes.$sort", $dir);
        }

        $query->orderByDesc('credit_notes.id');

        $creditNotes = $query
            ->paginate(20)
            ->withQueryString();

        // filter dropdown options
        $companies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id','name']);
        return view('tenant.credit_notes.index', compact(
            'tenant',
            'canExport',
            'creditNotes',
            'companies',
            'q',
            'company_id',
            'state',
            'sort',
            'dir'
        ));
    }

    public function create(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');
        $this->authorize('create', CreditNote::class);

        $prefillCompanyId = $request->integer('company_id') ?: null;
        $prefillContactId = $request->integer('contact_id') ?: null;
        $prefillInvoiceId = $request->integer('invoice_id') ?: null;

        $companies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id','name']);

        // If contacts table has company_id later, you can filter. For now keep as is.
        $contacts = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id','name']);

        // OPEN invoices list (optional apply dropdown)
        $openInvoices = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->when($prefillCompanyId, fn($q) => $q->where('company_id', $prefillCompanyId))
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get(['id','company_id','invoice_number','issued_at','total']);

        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id','name','sku','description','unit_rate']);

        $taxTypes = TaxType::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id','name','rate']);

        $defaultTaxTypeId = $taxTypes->first()?->id;

        return view('tenant.credit_notes.create', compact(
            'tenant','companies','contacts','openInvoices','products','taxTypes','defaultTaxTypeId',
            'prefillCompanyId','prefillContactId','prefillInvoiceId'
        ));
    }


   public function store(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');
        $this->authorize('create', CreditNote::class);

        $data = $request->validate([
            'company_id' => ['required','integer'],
            'contact_id' => ['nullable','integer'],
            'issued_at' => ['required','date'],
            'reason' => ['nullable','string','max:180'],
            'notes' => ['nullable','string'],
            'apply_invoice_id' => ['nullable','integer'],

            'items' => ['required','array','min:1'],
            'items.*.product_id' => ['nullable','integer'],
            'items.*.name' => ['nullable','string','max:255'],
            'items.*.sku' => ['nullable','string','max:100'],
            'items.*.description' => ['nullable','string'],
            'items.*.qty' => ['required','numeric','min:0.01'],
            'items.*.unit_price' => ['required','numeric','min:0'],
            'items.*.discount_pct' => ['nullable','numeric','min:0','max:100'],
            'items.*.tax_type_id' => ['nullable','integer'],
        ]);

        // Hard guard: company belongs to tenant
        abort_unless(
            Company::where('tenant_id', $tenant->id)->where('id', $data['company_id'])->exists(),
            404
        );

        return DB::transaction(function () use ($tenant, $tenantKey, $data) {

            // ✅ CN Number (00001) from tenant_counters
            $nextNumber = app(DocumentNumberService::class)->nextCreditNoteNumber($tenant->id);

            // Preload tax rates by id
            $taxRates = TaxType::query()
                ->where('tenant_id', $tenant->id)
                ->pluck('rate', 'id');

            $subtotal = 0;
            $discountTotal = 0;
            $taxTotal = 0;
            $grandTotal = 0;

            $computedItems = [];

            foreach ($data['items'] as $it) {
                $qty = round((float) $it['qty'], 2);
                $rate = round((float) $it['unit_price'], 2);

                $discPct = round((float) ($it['discount_pct'] ?? 0), 2);
                $discPct = max(0, min(100, $discPct));

                $lineSubtotal = round($qty * $rate, 2);
                $lineDiscount = round($lineSubtotal * ($discPct / 100), 2);
                $lineNet = round($lineSubtotal - $lineDiscount, 2);

                $taxTypeId = $it['tax_type_id'] ?? null;
                $vatRate = $taxTypeId ? (float) ($taxRates[$taxTypeId] ?? 0) : 0;

                $lineTax = round($lineNet * ($vatRate / 100), 2);

                $lineTotal = $lineNet; // excl VAT
                $lineTotalIncl = round($lineNet + $lineTax, 2);

                $subtotal += $lineSubtotal;
                $discountTotal += $lineDiscount;
                $taxTotal += $lineTax;
                $grandTotal += $lineTotalIncl;

                $computedItems[] = [
                    'product_id' => $it['product_id'] ?? null,
                    'tax_type_id' => $taxTypeId,
                    'name' => $it['name'] ?? null,
                    'sku' => $it['sku'] ?? null,
                    'description' => $it['description'] ?? null,
                    'qty' => $qty,
                    'unit_price' => $rate,
                    'discount_pct' => $discPct,
                    'line_subtotal' => $lineSubtotal,
                    'line_discount' => $lineDiscount,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                    'line_total_incl' => $lineTotalIncl,
                ];
            }

            $cn = CreditNote::create([
                'tenant_id' => $tenant->id,
                'company_id' => $data['company_id'],
                'issued_at' => $data['issued_at'],
                'credit_note_number' => $nextNumber,      // ✅ 00001
                'amount' => round($grandTotal, 2),        // ✅ totals from items
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => auth()->id(),     // ✅ now stored
            ]);

            foreach ($computedItems as $row) {
                CreditNoteItem::create(array_merge($row, [
                    'tenant_id' => $tenant->id,
                    'credit_note_id' => $cn->id,
                ]));
            }

            // Auto allocate
            $this->autoAllocateToInvoices(
                tenantId: $tenant->id,
                companyId: (int) $data['company_id'],
                amount: (float) $cn->amount,
                applyInvoiceId: $data['apply_invoice_id'] ?? null,
                paymentId: null,
                creditNoteId: $cn->id,
                appliedAt: $data['issued_at']
            );

            $invoiceIds = DB::table('transaction_allocations')
                ->where('tenant_id', $tenant->id)
                ->where('credit_note_id', $cn->id)
                ->pluck('invoice_id')
                ->unique()
                ->values()
                ->all();

            app(\App\Services\InvoicePaymentStatusService::class)->syncMany($tenant->id, $invoiceIds);


            return redirect()
                ->route('tenant.credit-notes.show', ['tenant' => $tenantKey, 'credit_note' => $cn->id])
                ->with('success', 'Credit Note created (with items) and allocated.');
        });
    }


    public function show(string $tenantKey, CreditNote $creditNote)
    {
        $tenant = app('tenant');
        $this->authorize('view', $creditNote);
        abort_unless((int) $creditNote->tenant_id === (int) $tenant->id, 404);

        $creditNote->load([
            'company',
            'contact',
            'invoice',     // optional, if you keep invoice_id on credit notes
            'items.taxType', // if CreditNoteItem has taxType() relationship
            'allocations.invoice', // if allocation has invoice() relationship
            'refunds',
        ]);

        // Allocations (if you don't have relationships wired yet, use queries)
        $allocations = TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('credit_note_id', $creditNote->id)
            ->with('invoice') // optional
            ->orderBy('applied_at')
            ->orderBy('id')
            ->get();

        // Refunds
        $refunds = \App\Models\CreditNoteRefund::query()
            ->where('tenant_id', $tenant->id)
            ->where('credit_note_id', $creditNote->id)
            ->orderBy('refunded_at')
            ->orderBy('id')
            ->get();

        // Totals
        $amount = (float) ($creditNote->amount ?? 0);
        $allocated = (float) $allocations->sum('amount_applied');
        $refunded = (float) $refunds->sum('amount');
        $available = max(0, round($amount - $allocated - $refunded, 2));

        // Address strings (optional, if you already do this on invoices)
        $billTo = null;
        $shipTo = null;

        // If Company has addresses relationship preloaded in Company show, here you can fetch defaults:
        if ($creditNote->company && method_exists($creditNote->company, 'addresses')) {
            $creditNote->company->loadMissing('addresses.country', 'addresses.subdivision');

            $billing =
                $creditNote->company->addresses->firstWhere('is_default_billing', 1)
                ?? $creditNote->company->addresses->firstWhere('type', 'billing')
                ?? $creditNote->company->addresses->first();

            $shipping =
                $creditNote->company->addresses->firstWhere('is_default_shipping', 1)
                ?? $creditNote->company->addresses->firstWhere('type', 'shipping')
                ?? $creditNote->company->addresses->first();

            $fmtAddr = function ($a) {
                if (!$a) return null;
                $lines = array_filter([
                    data_get($a, 'label'),
                    data_get($a, 'attention'),
                    data_get($a, 'line1'),
                    data_get($a, 'line2'),
                    data_get($a, 'city'),
                    data_get($a, 'postal_code'),
                    optional(data_get($a, 'subdivision'))->name ?: data_get($a, 'subdivision_text'),
                    optional(data_get($a, 'country'))->name,
                ]);
                return implode("\n", $lines);
            };

            $billTo = $fmtAddr($billing);
            $shipTo = $fmtAddr($shipping);
        }

        return view('tenant.credit_notes.show', compact(
            'tenant',
            'creditNote',
            'allocations',
            'refunds',
            'amount',
            'allocated',
            'refunded',
            'available',
            'billTo',
            'shipTo'
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

    public function export(Request $request, string $tenantKey): StreamedResponse
    {
        $tenant = app('tenant');
        abort_unless(auth()->user()->can('export.run'), 403);

        if (!tenant_feature($tenant, 'export')) {
            return back()->with('error', 'Export to Excel is available on the Premium plan.');
        }

        $q = trim((string) $request->query('q', ''));
        $company_id = (string) $request->query('company_id', '');
        $state = (string) $request->query('state', '');

        $sort = (string) $request->query('sort', 'issued_at');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'issued_at','credit_note_number','company','amount','allocated','refunded','available','created_at','updated_at'
        ];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'issued_at';

        // allocations per credit note
        $allocSub = DB::table('transaction_allocations')
            ->select('credit_note_id', DB::raw('SUM(amount_applied) as allocated_total'))
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('credit_note_id')
            ->groupBy('credit_note_id');

        // refunds per credit note  ✅ matches your table/columns
        $refundSub = DB::table('credit_note_refunds')
            ->select('credit_note_id', DB::raw('SUM(amount) as refunded_total'))
            ->where('tenant_id', $tenant->id)
            ->groupBy('credit_note_id');

        $query = CreditNote::query()
            ->where('credit_notes.tenant_id', $tenant->id)
            ->with(['company'])
            ->leftJoinSub($allocSub, 'alloc', fn ($j) => $j->on('alloc.credit_note_id', '=', 'credit_notes.id'))
            ->leftJoinSub($refundSub, 'ref', fn ($j) => $j->on('ref.credit_note_id', '=', 'credit_notes.id'))
            ->select([
                'credit_notes.*',
                DB::raw('COALESCE(alloc.allocated_total, 0) as allocated_total'),
                DB::raw('COALESCE(ref.refunded_total, 0) as refunded_total'),
                DB::raw('(credit_notes.amount - COALESCE(alloc.allocated_total, 0) - COALESCE(ref.refunded_total, 0)) as available_total'),
            ])
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($x) use ($q) {
                    $x->where('credit_notes.credit_note_number', 'like', "%{$q}%")
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($company_id !== '', fn ($qry) => $qry->where('credit_notes.company_id', (int) $company_id))
            ->when($state !== '', function ($qry) use ($state) {
                $tol = 0.01;

                if ($state === 'available') {
                    $qry->whereRaw('(credit_notes.amount - COALESCE(alloc.allocated_total, 0) - COALESCE(ref.refunded_total, 0)) > ?', [$tol]);
                }
                if ($state === 'allocated') {
                    $qry->whereRaw('ABS((credit_notes.amount - COALESCE(alloc.allocated_total, 0) - COALESCE(ref.refunded_total, 0))) <= ?', [$tol])
                        ->whereRaw('COALESCE(ref.refunded_total, 0) <= ?', [$tol]);
                }
                if ($state === 'refunded') {
                    $qry->whereRaw('COALESCE(ref.refunded_total, 0) > ?', [$tol]);
                }
            });

        // sorting
        if ($sort === 'company') {
            $query->leftJoin('companies', function ($join) use ($tenant) {
                $join->on('companies.id', '=', 'credit_notes.company_id')
                    ->where('companies.tenant_id', '=', $tenant->id);
            })
            ->addSelect(DB::raw('companies.name as company_sort'))
            ->orderBy('company_sort', $dir);
        } elseif (in_array($sort, ['allocated','refunded','available'], true)) {
            $map = [
                'allocated' => 'allocated_total',
                'refunded'  => 'refunded_total',
                'available' => 'available_total',
            ];
            $query->orderBy($map[$sort], $dir);
        } else {
            $query->orderBy("credit_notes.$sort", $dir);
        }

        $query->orderByDesc('credit_notes.id');

        $rows = $query->get();

        $filename = 'credit-notes-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Date',
                'Credit Note #',
                'Customer',
                'Amount',
                'Allocated',
                'Refunded',
                'Available',
                'Created',
                'Updated',
            ]);

            foreach ($rows as $cn) {
                fputcsv($out, [
                    $cn->issued_at ? \Illuminate\Support\Carbon::parse($cn->issued_at)->format('Y-m-d') : '',
                    $cn->credit_note_number ?: ('CN-' . $cn->id),
                    $cn->company?->name ?? '',
                    number_format((float) $cn->amount, 2, '.', ''),
                    number_format((float) ($cn->allocated_total ?? 0), 2, '.', ''),
                    number_format((float) ($cn->refunded_total ?? 0), 2, '.', ''),
                    number_format((float) ($cn->available_total ?? 0), 2, '.', ''),
                    optional($cn->created_at)->format('Y-m-d H:i'),
                    optional($cn->updated_at)->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

}
