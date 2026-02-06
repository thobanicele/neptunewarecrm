<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class InvoiceEmailController extends Controller
{
    public function send(Request $request, string $tenantKey, Invoice $invoice)
    {
        $tenant = app('tenant');
        abort_unless((int)$invoice->tenant_id === (int)$tenant->id, 404);

        if (!tenant_feature($tenant, 'invoice_email_send')) {
            return back()->with('error', 'Email sending is not enabled for your plan.');
        }

        $invoice->load([
            'items' => fn($q) => $q->orderBy('position'),
            'company',
            'contact',
            'tenant',
        ]);

        $to = $request->input('to')
            ?: $invoice->contact?->email
            ?: null;

        if (!$to) {
            return back()->with('error', 'No email address found. Add a contact email or specify one.');
        }

        $watermark = tenant_feature($tenant, 'invoice_pdf_watermark');
        $pdf = Pdf::loadView('tenant.invoices.pdf', compact('tenant', 'invoice', 'watermark'));

        Mail::to($to)->send(new InvoiceMail($tenant, $invoice, $pdf->output()));

        return back()->with('success', 'Invoice emailed successfully.');
    }
}
