<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ActivityLogger;

class SalesOrderPdfController extends Controller
{
    public function stream(Tenant $tenant, SalesOrder $salesOrder)
    {
        $tenant = app('tenant');

        $this->authorize('view', $salesOrder);
        abort_unless((int)$salesOrder->tenant_id === (int)$tenant->id, 404);

        $salesOrder->load([
            'items' => fn($q) => $q->orderBy('position'),
            'company','contact','deal','tenant',
        ]);

        app(ActivityLogger::class)->log($tenant->id, 'sales_order.pdf_viewed', $salesOrder, [
            'sales_order_number' => $salesOrder->sales_order_number,
        ]);

        return Pdf::loadView('tenant.sales_orders.pdf', compact('tenant','salesOrder'))
            ->stream($salesOrder->sales_order_number . '.pdf');
    }

    public function download(Tenant $tenant, SalesOrder $salesOrder)
    {
        $tenant = app('tenant');

        $this->authorize('view', $salesOrder);
        abort_unless((int)$salesOrder->tenant_id === (int)$tenant->id, 404);

        $salesOrder->load([
            'items' => fn($q) => $q->orderBy('position'),
            'company','contact','deal','tenant',
        ]);

        app(ActivityLogger::class)->log($tenant->id, 'sales_order.pdf_downloaded', $salesOrder, [
            'sales_order_number' => $salesOrder->sales_order_number,
        ]);

        return Pdf::loadView('tenant.sales_orders.pdf', compact('tenant','salesOrder'))
            ->download($salesOrder->sales_order_number . '.pdf');
    }
}

