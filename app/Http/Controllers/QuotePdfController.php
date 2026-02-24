<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\ActivityLogger;

class QuotePdfController extends Controller
{
    public function stream(Tenant $tenant, Quote $quote)
    {
        $tenant = app('tenant');
        $this->authorize('view', $quote);
        abort_unless((int)$quote->tenant_id === (int)$tenant->id, 404);

        $quote->load([
            'items' => fn($q) => $q->orderBy('position'),
            'company',
            'contact',
            'deal',
            'tenant',
        ]);

                app(ActivityLogger::class)->log($tenant->id, 'quote.pdf_viewed', $quote, [
            'quote_number' => $quote->quote_number,
        ]);



        return Pdf::loadView('tenant.quotes.pdf', compact('tenant','quote'))
            ->stream($quote->quote_number . '.pdf');
    }

    public function download(\App\Models\Tenant $tenant, Quote $quote)
    {
        $tenant = app('tenant');
        $this->authorize('view', $quote);
        abort_unless((int)$quote->tenant_id === (int)$tenant->id, 404);

         $quote->load([
            'items' => fn($q) => $q->orderBy('position'),
            'company',
            'contact',
            'deal',
            'tenant',
        ]);

                app(ActivityLogger::class)->log($tenant->id, 'quote.pdf_downloaded', $quote, [
            'quote_number' => $quote->quote_number,
        ]);


        return Pdf::loadView('tenant.quotes.pdf', compact('tenant','quote'))
            ->download($quote->quote_number . '.pdf');
    }
}


