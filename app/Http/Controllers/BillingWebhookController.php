<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $event = $request->input('event');
        $data  = $request->input('data', []);

        // helpful tenant id passed in metadata during initialize
        $tenantId = data_get($data, 'metadata.tenant_id');

        if ($event === 'charge.success') {
            if ($tenantId) {
                $sub = Subscription::firstOrCreate(['tenant_id' => $tenantId], [
                    'provider' => 'paystack',
                    'plan' => 'premium',
                    'cycle' => data_get($data, 'metadata.cycle', 'monthly'),
                    'status' => 'active',
                ]);

                $sub->status = 'active';
                $sub->paystack_customer_code = data_get($data, 'customer.customer_code');
                $sub->authorization_code = data_get($data, 'authorization.authorization_code');
                $sub->authorization_signature = data_get($data, 'authorization.signature');
                $sub->meta = array_merge((array) $sub->meta, [
                    'last_reference' => data_get($data, 'reference'),
                ]);
                $sub->save();

                Tenant::where('id', $tenantId)->update(['plan' => 'premium']);
            }
        }

        if ($event === 'subscription.create') {
            if ($tenantId) {
                $sub = Subscription::firstOrCreate(['tenant_id' => $tenantId], [
                    'provider' => 'paystack',
                    'plan' => 'premium',
                    'cycle' => data_get($data, 'metadata.cycle', 'monthly'),
                    'status' => 'active',
                ]);

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

