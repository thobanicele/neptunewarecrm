<p>Hello,</p>

<p>Please find attached your invoice <strong>{{ $invoice->invoice_number }}</strong> from
    <strong>{{ $tenant->name }}</strong>.</p>

@if (!empty($invoice->reference))
    <p><strong>Reference:</strong> {{ $invoice->reference }}</p>
@endif

<p><strong>Total:</strong> {{ $invoice->currency === 'ZAR' ? 'R' : $invoice->currency }}
    {{ number_format((float) $invoice->total, 2) }}</p>

<p>Regards,<br>{{ $tenant->name }}</p>
