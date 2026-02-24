<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\EcommerceOrder;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class EcommerceOrderCustomerLinkController extends Controller
{
    public function store(Request $request, string $tenant, EcommerceOrder $ecommerceOrder)
    {
        $t = tenant();
        abort_unless((int) $ecommerceOrder->tenant_id === (int) $t->id, 404);

        $data = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'contact_id' => ['nullable', 'integer'],
        ]);

        if (!empty($data['company_id'])) {
            Company::where('tenant_id', $t->id)->findOrFail((int) $data['company_id']);
        }

        if (!empty($data['contact_id'])) {
            Contact::where('tenant_id', $t->id)->findOrFail((int) $data['contact_id']);
        }

        $ecommerceOrder->company_id = $data['company_id'] ?? null;
        $ecommerceOrder->contact_id = $data['contact_id'] ?? null;
        $ecommerceOrder->save();

        if (class_exists(ActivityLogger::class)) {
            app(ActivityLogger::class)->log(
                $t->id,
                'ecommerce_order.customer_linked',
                $ecommerceOrder,
                [
                    'company_id' => $ecommerceOrder->company_id,
                    'contact_id' => $ecommerceOrder->contact_id,
                ],
                auth()->id()
            );
        }

        return back()->with('success', 'Customer linked to ecommerce order.');
    }
}
