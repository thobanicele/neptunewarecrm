<?php

namespace App\Http\Controllers;

use App\Models\EcommerceOrder;
use App\Models\Tenant;
use App\Models\ActivityLog;
use App\Models\SalesOrder;
use App\Models\Invoice;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;

class EcommerceOrdersController extends Controller
{
    public function index(Request $request, string $tenant)
    {
        $tenantModel = app('tenant');

        if (function_exists('tenant_feature')) {
            abort_unless(tenant_feature($tenantModel, 'ecommerce_module'), 403);
        }

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $pay = trim((string) $request->get('payment_status', ''));
        $ful = trim((string) $request->get('fulfillment_status', ''));

        $orders = EcommerceOrder::query()
            ->where('tenant_id', $tenantModel->id)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('external_order_id', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%")
                        ->orWhere('customer_email', 'like', "%{$q}%")
                        ->orWhere('customer_phone', 'like', "%{$q}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($pay !== '', fn ($query) => $query->where('payment_status', $pay))
            ->when($ful !== '', fn ($query) => $query->where('fulfillment_status', $ful))
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('tenant.ecommerce_orders.index', compact('orders', 'q', 'status', 'pay', 'ful'));
    }

    public function show(Request $request, string $tenant, EcommerceOrder $ecommerceOrder)
    {
        $tenantModel = app('tenant');
        $companies = Company::where('tenant_id', $tenantModel->id)->orderBy('name')->get(['id','name']);
        $contacts = Contact::where('tenant_id', $tenantModel->id)->orderBy('name')->get(['id','name']);

        if (function_exists('tenant_feature')) {
            abort_unless(tenant_feature($tenantModel, 'ecommerce_module'), 403);
        }

        abort_unless((int) $ecommerceOrder->tenant_id === (int) $tenantModel->id, 404);

        $ecommerceOrder->load([
            'items',
            'convertedSalesOrder',
            'activityLogs.user', // if ActivityLog has user() relation
        ]);

        $salesOrder = $ecommerceOrder->convertedSalesOrder;

        // Find invoice created from this SO (idempotent mapping via invoices.source/source_id)
        $invoice = null;
        if ($salesOrder) {
            $invoice = Invoice::where('tenant_id', $tenantModel->id)
                ->where('source', 'sales_order')
                ->where('source_id', $salesOrder->id)
                ->first();
        }

        // Button gating
        $canConvert = empty($ecommerceOrder->converted_sales_order_id)
            && !in_array($ecommerceOrder->payment_status, ['failed', 'refunded'], true);

        $eligibleForInvoice = ($ecommerceOrder->payment_status === 'paid'
            && $ecommerceOrder->fulfillment_status === 'fulfilled');

        $canCreateInvoice = $eligibleForInvoice && !$invoice;

        $tab = $request->get('tab', 'preview');

        return view('tenant.ecommerce_orders.show', compact(
            'ecommerceOrder',
            'salesOrder',
            'invoice',
            'canConvert',
            'eligibleForInvoice',
            'canCreateInvoice',
            'tab',
            'companies',
            'contacts'
        ));
    }
}
