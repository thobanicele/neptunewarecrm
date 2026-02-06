<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CreditNote;
use App\Models\CreditNoteRefund;
use App\Models\TransactionAllocation;

class CreditNoteRefundController extends Controller
{
    public function create(string $tenantKey, CreditNote $creditNote)
    {
        $tenant = app('tenant');
        abort_unless($creditNote->tenant_id === $tenant->id, 404);

        $allocated = (float) TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('credit_note_id', $creditNote->id)
            ->sum('amount_applied');

        $refunded = (float) CreditNoteRefund::query()
            ->where('tenant_id', $tenant->id)
            ->where('credit_note_id', $creditNote->id)
            ->sum('amount');

        $remaining = max(0, (float)$creditNote->amount - $allocated - $refunded);

        return view('tenant.credit_notes.refund', compact('tenant','creditNote','remaining'));
    }

    public function store(Request $request, string $tenantKey, CreditNote $creditNote)
    {
        $tenant = app('tenant');
        abort_unless($creditNote->tenant_id === $tenant->id, 404);

        $allocated = (float) TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('credit_note_id', $creditNote->id)
            ->sum('amount_applied');

        $refunded = (float) CreditNoteRefund::query()
            ->where('tenant_id', $tenant->id)
            ->where('credit_note_id', $creditNote->id)
            ->sum('amount');

        $remaining = max(0, (float)$creditNote->amount - $allocated - $refunded);

        $data = $request->validate([
            'refunded_at' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'method' => ['nullable','string','max:50'],
            'reference' => ['nullable','string','max:120'],
            'notes' => ['nullable','string'],
        ]);

        if ((float)$data['amount'] > $remaining) {
            return back()->withErrors(['amount' => 'Refund exceeds available credit.'])->withInput();
        }

        CreditNoteRefund::create([
            'tenant_id' => $tenant->id,
            'company_id' => $creditNote->company_id,
            'credit_note_id' => $creditNote->id,
            'refunded_at' => $data['refunded_at'],
            'amount' => $data['amount'],
            'method' => $data['method'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by_user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('tenant.credit-notes.show', ['tenant' => $tenantKey, 'credit_note' => $creditNote->id])
            ->with('success', 'Refund recorded.');
    }
}

