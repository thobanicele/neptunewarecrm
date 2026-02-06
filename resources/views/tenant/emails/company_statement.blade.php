<p>Hello,</p>

<p>Please find attached your statement from <strong>{{ $tenant->name }}</strong>.</p>

<p>
    <strong>Company:</strong> {{ $company->name }}<br>
    <strong>Period:</strong>
    {{ $from?->format('d/m/Y') ?? '—' }} to {{ $to?->format('d/m/Y') ?? '—' }}
</p>

<p>Regards,<br>{{ $tenant->name }}</p>
