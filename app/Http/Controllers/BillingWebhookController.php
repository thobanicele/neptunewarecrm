<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Tenant;

class BillingWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $secret = config('services.paystack.secret');

        $computed = hash_hmac('sha512', $request->getContent(), $secret);
        if (!$signature || !hash_equals($computed, $signature)) {
            return response()->json(['ok' => false], 401);
        }

        $event = (string) $request->input('event', '');
        $data  = (array) $request->input('data', []);

        $tenantId = (int) data_get($data, 'metadata.tenant_id', 0);

        // ✅ plan + cycle from metadata (support premium + business)
        $plan = (string) data_get($data, 'metadata.plan', 'premium');
        if (!in_array($plan, ['premium', 'business'], true)) $plan = 'premium';

        $cycle = (string) data_get($data, 'metadata.cycle', 'monthly');
        if (!in_array($cycle, ['monthly', 'yearly'], true)) $cycle = 'monthly';

        if ($event === 'charge.success') {
            if ($tenantId) {
                $sub = Subscription::firstOrCreate(['tenant_id' => $tenantId], [
                    'provider' => 'paystack',
                    'plan' => $plan,
                    'cycle' => $cycle,
                    'status' => 'active',
                ]);

                $sub->status = 'active';
                $sub->plan = $plan;
                $sub->cycle = $cycle;
                $sub->paystack_customer_code = data_get($data, 'customer.customer_code');
                $sub->authorization_code = data_get($data, 'authorization.authorization_code');
                $sub->authorization_signature = data_get($data, 'authorization.signature');
                $sub->meta = array_merge((array) $sub->meta, [
                    'last_reference' => data_get($data, 'reference'),
                ]);
                $sub->save();

                Tenant::where('id', $tenantId)->update(['plan' => $plan]);
            }
        }

        if ($event === 'subscription.create') {
            if ($tenantId) {
                $sub = Subscription::firstOrCreate(['tenant_id' => $tenantId], [
                    'provider' => 'paystack',
                    'plan' => $plan,
                    'cycle' => $cycle,
                    'status' => 'active',
                ]);

                $sub->plan = $plan;
                $sub->cycle = $cycle;
                $sub->paystack_subscription_code = data_get($data, 'subscription_code');
                $sub->paystack_email_token = data_get($data, 'email_token');
                $sub->status = 'active';
                $sub->save();
            }
        }

        // You can handle: subscription.disable, invoice.payment_failed, etc later
        return response()->json(['ok' => true]);
    }
}

