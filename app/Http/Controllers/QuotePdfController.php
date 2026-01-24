<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotePdfController extends Controller
{
    public function stream(\App\Models\Tenant $tenant, Quote $quote)
    {
        $tenant = app('tenant');
        abort_unless((int)$quote->tenant_id === (int)$tenant->id, 404);

        $quote->load([
            'items' => fn($q) => $q->orderBy('position'),
            'company',
            'contact',
            'deal',
            'tenant',
        ]);


        return Pdf::loadView('tenant.quotes.pdf', compact('tenant','quote'))
            ->stream($quote->quote_number . '.pdf');
    }

    public function download(\App\Models\Tenant $tenant, Quote $quote)
    {
        $tenant = app('tenant');
        abort_unless((int)$quote->tenant_id === (int)$tenant->id, 404);

         $quote->load([
            'items' => fn($q) => $q->orderBy('position'),
            'company',
            'contact',
            'deal',
            'tenant',
        ]);

        return Pdf::loadView('tenant.quotes.pdf', compact('tenant','quote'))
            ->download($quote->quote_number . '.pdf');
    }
}


