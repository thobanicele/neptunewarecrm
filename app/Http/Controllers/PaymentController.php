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

class PaymentController extends Controller
{
    public function create(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');

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

        $invoices = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->when($prefillCompanyId, fn($q) => $q->where('company_id', $prefillCompanyId))
            ->orderByDesc('issued_at')
            ->get(['id','company_id','issued_at','total']);

        return view('tenant.payments.create', compact(
            'tenant','companies','contacts','invoices',
            'prefillCompanyId','prefillContactId','prefillInvoiceId'
        ));
    }

    public function store(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'company_id' => ['required','integer'],
            'contact_id' => ['nullable','integer'],
            'received_at' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'method' => ['nullable','string','max:50'],
            'reference' => ['nullable','string','max:120'],
            'notes' => ['nullable','string'],
            'apply_invoice_id' => ['nullable','integer'],
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'company_id' => $data['company_id'],
            'contact_id' => $data['contact_id'] ?? null,
            'received_at' => $data['received_at'],
            'amount' => $data['amount'],
            'method' => $data['method'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by_user_id' => auth()->id(),
        ]);

        // Auto allocate
        $this->autoAllocateToInvoices(
            tenantId: $tenant->id,
            companyId: (int) $data['company_id'],
            amount: (float) $data['amount'],
            applyInvoiceId: $data['apply_invoice_id'] ?? null,
            paymentId: $payment->id,
            creditNoteId: null,
            appliedAt: $data['received_at']
        );

        return redirect()
            ->route('tenant.payments.show', ['tenant' => $tenantKey, 'payment' => $payment->id])
            ->with('success', 'Payment captured and allocated.');
    }

    public function show(string $tenantKey, Payment $payment)
    {
        $tenant = app('tenant');
        abort_unless($payment->tenant_id === $tenant->id, 404);

        $payment->load('company','contact');

        $allocations = TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('payment_id', $payment->id)
            ->get();

        return view('tenant.payments.show', compact('tenant','payment','allocations'));
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

}