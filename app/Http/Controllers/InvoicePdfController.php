<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfController extends Controller
{
    public function stream(\App\Models\Tenant $tenant, Invoice $invoice)
    {
        $tenant = app('tenant');
        abort_unless((int)$invoice->tenant_id === (int)$tenant->id, 404);

        $invoice->load([
            'items' => fn($q) => $q->orderBy('position'),
            'company',
            'contact',
            'deal',
            'quote',
            'tenant',
        ]);

        $watermark = tenant_feature($tenant, 'invoice_pdf_watermark');

        return Pdf::loadView('tenant.invoices.pdf', compact('tenant','invoice','watermark'))
            ->stream($invoice->invoice_number . '.pdf');
    }

    public function download(\App\Models\Tenant $tenant, Invoice $invoice)
    {
        $tenant = app('tenant');
        abort_unless((int)$invoice->tenant_id === (int)$tenant->id, 404);

        $invoice->load([
            'items' => fn($q) => $q->orderBy('position'),
            'company',
            'contact',
            'deal',
            'quote',
            'tenant',
        ]);

        $watermark = tenant_feature($tenant, 'invoice_pdf_watermark');

        return Pdf::loadView('tenant.invoices.pdf', compact('tenant','invoice','watermark'))
            ->download($invoice->invoice_number . '.pdf');
    }
}



