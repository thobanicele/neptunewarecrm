<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditNotePdfController extends Controller
{
    public function stream(Request $request, string $tenantKey, CreditNote $creditNote)
    {
        $tenant = app('tenant');
        abort_unless((int) $creditNote->tenant_id === (int) $tenant->id, 404);

        $creditNote->load([
            'company',
            'contact',
            'items.taxType',
        ]);

        // Optional: build address text if you want (safe fallback)
        $billTo = $this->companyBillingText($creditNote);

        $pdf = Pdf::loadView('tenant.credit_notes.pdf', [
            'tenant' => $tenant,
            'creditNote' => $creditNote,
            'billTo' => $billTo,
        ])->setPaper('a4');

        $filename = 'Credit-Note-' . ($creditNote->credit_note_number ?? $creditNote->id) . '.pdf';

        return $pdf->stream($filename);
    }

    public function download(Request $request, string $tenantKey, CreditNote $creditNote)
    {
        $tenant = app('tenant');
        abort_unless((int) $creditNote->tenant_id === (int) $tenant->id, 404);

        $creditNote->load([
            'company',
            'contact',
            'items.taxType',
        ]);

        $billTo = $this->companyBillingText($creditNote);

        $pdf = Pdf::loadView('tenant.credit_notes.pdf', [
            'tenant' => $tenant,
            'creditNote' => $creditNote,
            'billTo' => $billTo,
        ])->setPaper('a4');

        $filename = 'Credit-Note-' . ($creditNote->credit_note_number ?? $creditNote->id) . '.pdf';

        return $pdf->download($filename);
    }

    private function companyBillingText(CreditNote $creditNote): string
    {
        $company = $creditNote->company;
        if (!$company) return '';

        // If you have addresses relationship
        if (method_exists($company, 'addresses') && $company->relationLoaded('addresses') === false) {
            $company->load('addresses.country', 'addresses.subdivision');
        }

        if (!method_exists($company, 'addresses')) {
            return '';
        }

        $billing =
            $company->addresses->firstWhere('is_default_billing', 1) ??
            $company->addresses->firstWhere('type', 'billing') ??
            $company->addresses->first();

        if (!$billing) return '';

        $parts = array_filter([
            $billing->label ?: null,
            $billing->attention ?: null,
            $billing->line1 ?: null,
            $billing->line2 ?: null,
            $billing->city ?: null,
            $billing->subdivision_text ?: (optional($billing->subdivision)->name ?? null),
            $billing->postal_code ?: null,
            optional($billing->country)->name ?? null,
        ]);

        return trim(implode("\n", $parts));
    }
}

