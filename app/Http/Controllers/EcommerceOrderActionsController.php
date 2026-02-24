<?php

namespace App\Http\Controllers;

use App\Models\EcommerceOrder;
use App\Models\SalesOrder;
use App\Services\Ecommerce\EcommerceOrderConverter;
use Illuminate\Http\Request;

class EcommerceOrderActionsController extends Controller
{
   public function convert(Request $request, string $tenant, EcommerceOrder $ecommerceOrder, EcommerceOrderConverter $converter)
    {
        $tenantModel = app('tenant');
        abort_unless((int) $ecommerceOrder->tenant_id === (int) $tenantModel->id, 404);

        // Feature gate (if you have tenant_feature helper)
        if (function_exists('tenant_feature')) {
            abort_unless(tenant_feature($tenantModel, 'ecommerce_convert_to_sales_order'), 403);
        }

        // Policy C (hybrid): paid OR manual approve
        // Manual approve = this action itself, but we still block obvious bad states.
        if (in_array($ecommerceOrder->payment_status, ['failed', 'refunded'], true)) {
            return back()->with('error', 'Cannot convert an order with failed/refunded payment.');
        }

        $so = $converter->convertToSalesOrder($ecommerceOrder, auth()->id());

        return redirect()->to(tenant_route('tenant.sales-orders.show', ['sales_order' => $so->id]))
            ->with('success', 'Converted to Sales Order.');
    }

    public function createInvoice(Request $request, string $tenant, EcommerceOrder $ecommerceOrder, EcommerceOrderConverter $converter)
    {
        $tenantModel = app('tenant');
        abort_unless((int) $ecommerceOrder->tenant_id === (int) $tenantModel->id, 404);

        if (function_exists('tenant_feature')) {
            abort_unless(tenant_feature($tenantModel, 'ecommerce_module'), 403);
        }

        // Invoice policy: only when paid + fulfilled
        if ($ecommerceOrder->payment_status !== 'paid' || $ecommerceOrder->fulfillment_status !== 'fulfilled') {
            return back()->with('error', 'Invoice can only be created when the order is paid and fulfilled.');
        }

        // Ensure Sales Order exists (idempotent)
        $so = $converter->convertToSalesOrder($ecommerceOrder, auth()->id());

        // Create invoice (idempotent)
        $inv = $converter->createInvoiceFromSalesOrder($so, auth()->id());

        if (class_exists(\App\Services\ActivityLogger::class)) {
            app(\App\Services\ActivityLogger::class)->log(
                tenantId: $tenantModel->id,
                action: 'ecommerce_order.invoiced',
                subject: $ecommerceOrder,
                properties: [
                    'invoice_id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'sales_order_id' => $so->id,
                    'sales_order_number' => $so->sales_order_number,
                ],
                userId: auth()->id()
            );
        }

        return redirect()->to(tenant_route('tenant.invoices.show', ['invoice' => $inv->id]))
            ->with('success', 'Invoice created.');
    } 
}
