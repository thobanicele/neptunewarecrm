<?php

namespace App\Http\Controllers;

use App\Models\BillingPlan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BillingController extends Controller
{

    public function upgrade(string $tenant)
    {
        $tenant = app('tenant');

        $sub = \App\Models\Subscription::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan'     => $tenant->plan ?? config('plans.default_plan', 'free'),
                'provider' => 'paystack',
                'cycle'    => 'monthly',
                'status'   => 'inactive',
            ]
        );

        $pricing = config('plans.billing.pricing.premium'); // ['monthly'=>..., 'yearly'=>...]
        $paystack = [
            'currency' => config('plans.billing.currency', 'ZAR'),
            'monthly_plan_code' => config('plans.billing.paystack.premium_monthly_plan_code'),
            'yearly_plan_code'  => config('plans.billing.paystack.premium_yearly_plan_code'),
        ];

        $trialEnabled = (bool) config('plans.trial.enabled', true);
        $trialDays    = (int) config('plans.trial.days', 14);

        $trialEndsAt = $sub->trial_ends_at;
        $trialDaysLeft = $trialEndsAt ? max(0, now()->diffInDays($trialEndsAt, false)) : null;

        return view('tenant.billing.upgrade', compact(
            'tenant','sub','pricing','paystack','trialEnabled','trialDays','trialDaysLeft'
        ));
    }


    public function paystackInitialize(Request $request, string $tenant)
    {
        $tenant = app('tenant');

        $cycle = $request->input('cycle', 'monthly');
        abort_unless(in_array($cycle, ['monthly','yearly'], true), 400);

        $planCode = $cycle === 'monthly'
            ? config('plans.billing.paystack.premium_monthly_plan_code')
            : config('plans.billing.paystack.premium_yearly_plan_code');

        if (!$planCode) {
            return back()->with('error', 'Paystack plan code missing. Please set PAYSTACK_PLAN_PREMIUM_MONTHLY / YEARLY.');
        }

        $email = auth()->user()?->email;
        if (!$email) return back()->with('error', 'No billing email found for your account.');

        // Ensure subscription row exists
        $sub = Subscription::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan'     => $tenant->plan ?? 'free',
                'provider' => 'paystack',
                'cycle'    => $cycle,
                'status'   => 'inactive',
            ]
        );

        // ✅ Create trial (only if eligible and not already set)
        $trialEnabled = (bool) config('plans.trial.enabled', true);
        $trialDays = (int) config('plans.trial.days', 14);

        if ($trialEnabled && !$sub->trial_ends_at) {
            $sub->trial_ends_at = now()->addDays($trialDays);
        }

        $sub->cycle = $cycle;
        $sub->plan  = 'premium';
        $sub->save();

        // Paystack amount is in kobo (ZAR cents)
        $amount = (int) round((float) config("plans.billing.pricing.premium.{$cycle}.amount", 0) * 100);

        $payload = [
            'email' => $email,
            'amount' => $amount,
            'plan' => $planCode,
            'callback_url' => tenant_route('tenant.billing.paystack.callback', ['tenant' => $tenant->subdomain]),
            'metadata' => [
                'tenant_id' => $tenant->id,
                'cycle' => $cycle,
                'plan' => 'premium',
            ],
        ];

        // ✅ FIX: SSL (local only)
        $req = Http::withToken(config('services.paystack.secret'));
        if (app()->environment('local')) {
            $req = $req->withoutVerifying();
        }

        $response = $req->post('https://api.paystack.co/transaction/initialize', $payload);

        if (!$response->ok()) {
            return back()->with('error', 'Paystack initialization failed: ' . $response->body());
        }

        $res = $response->json();

        if (!data_get($res, 'status')) {
            return back()->with('error', data_get($res, 'message', 'Paystack initialization failed.'));
        }

        return redirect()->away(data_get($res, 'data.authorization_url'));
    }

    public function paystackCallback(Request $request, string $tenant)
    {
        $tenant = app('tenant');

        $reference = $request->query('reference');
        if (!$reference) return redirect()->route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain])
            ->with('error', 'Missing Paystack reference.');

        $verify = Http::withToken(config('services.paystack.secret'))
            ->get("https://api.paystack.co/transaction/verify/{$reference}")
            ->json();

        if (!data_get($verify, 'status') || data_get($verify, 'data.status') !== 'success') {
            return redirect()->route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain])
                ->with('error', 'Payment not successful.');
        }

        // Best: webhook will finalize subscription fields
        // But we can already flip plan access immediately:
        $tenant->plan = 'premium';
        $tenant->save();

        Subscription::where('tenant_id', $tenant->id)->update([
            'status' => 'active',
            'starts_at' => now(),
        ]);

        return redirect()->route('tenant.dashboard', ['tenant' => $tenant->subdomain])
            ->with('success', 'Payment successful! Premium enabled.');
    }

    public function paystackWebhook(Request $request)
    {
        // Verify Paystack signature
        $signature = $request->header('x-paystack-signature');
        $computed  = hash_hmac('sha512', $request->getContent(), config('paystack.secret_key'));

        if (!$signature || !hash_equals($computed, $signature)) {
            return response('Invalid signature', 400);
        }

        $event = (string) $request->input('event', '');
        $data  = (array) $request->input('data', []);

        // Extend on successful recurring charge
        if ($event === 'charge.success') {
            $meta = (array) data_get($data, 'metadata', []);
            $tenantId = (int) ($meta['tenant_id'] ?? 0);

            // If metadata not present (some recurring charges), we can fallback using customer_code lookups
            if (!$tenantId) {
                $customerCode = (string) data_get($data, 'customer.customer_code', '');
                if ($customerCode) {
                    $tenantId = (int) Subscription::where('paystack_customer_code', $customerCode)->value('tenant_id');
                }
            }

            if ($tenantId) {
                $this->extendFromCharge($tenantId, $data);
            }
        }

        // Mark canceled if subscription disabled
        if ($event === 'subscription.disable') {
            $subCode = (string) data_get($data, 'subscription_code', '');
            if ($subCode) {
                Subscription::where('paystack_subscription_code', $subCode)
                    ->update(['canceled_at' => now()]);
            }
        }

        return response('OK', 200);
    }

    private function ensurePaystackPlan(string $cycle): BillingPlan
    {
        $pricing = $this->pricing[$cycle];

        $existing = BillingPlan::where('provider', 'paystack')
            ->where('cycle', $cycle)
            ->first();

        if ($existing) return $existing;

        $payload = [
            'name' => 'NeptuneWare Premium ' . strtoupper($cycle),
            'interval' => $pricing['interval'],          // monthly|annually
            'amount' => (int) round($pricing['amount'] * 100),
            'currency' => 'ZAR',
        ];

        $res = Http::withToken(config('paystack.secret_key'))
            ->post('https://api.paystack.co/plan', $payload);

        if (!$res->ok() || !data_get($res->json(), 'status')) {
            abort(500, 'Failed to create Paystack plan.');
        }

        $planCode = (string) data_get($res->json(), 'data.plan_code');

        return BillingPlan::create([
            'provider' => 'paystack',
            'cycle' => $cycle,
            'amount' => $pricing['amount'],
            'currency' => 'ZAR',
            'interval' => $pricing['interval'],
            'plan_code' => $planCode,
        ]);
    }

    private function activateAndCreateRecurring(int $tenantId, array $verifiedTx): void
    {
        $meta = (array) data_get($verifiedTx, 'metadata', []);
        $cycle = (string) ($meta['cycle'] ?? 'monthly');
        if (!isset($this->pricing[$cycle])) $cycle = 'monthly';

        $reference = (string) data_get($verifiedTx, 'reference', '');

        // Paystack gives us authorization + customer details from verify
        $authorizationCode = (string) data_get($verifiedTx, 'authorization.authorization_code', '');
        $customerCode      = (string) data_get($verifiedTx, 'customer.customer_code', '');

        if (!$authorizationCode || !$customerCode) {
            abort(500, 'Paystack did not return authorization/customer. Cannot start recurring billing.');
        }

        $plan = $this->ensurePaystackPlan($cycle);

        DB::transaction(function () use ($tenantId, $cycle, $reference, $plan, $authorizationCode, $customerCode) {
            $sub = Subscription::firstOrCreate(
                ['tenant_id' => $tenantId],
                ['plan' => 'free', 'expires_at' => null]
            );

            // Idempotency: don’t double-process same transaction
            if ($sub->last_payment_ref === $reference) {
                return;
            }

            // 1) Activate premium immediately (first charge covers first period)
            $now = now();
            $base = $sub->expires_at ? Carbon::parse($sub->expires_at) : $now;
            if ($base->isPast()) $base = $now;

            $newExpiry = $base->copy()->addMonths($this->pricing[$cycle]['months']);

            $sub->plan = 'premium';
            $sub->provider = 'paystack';
            $sub->cycle = $cycle;
            $sub->expires_at = $newExpiry;
            $sub->paystack_plan_code = $plan->plan_code;
            $sub->paystack_customer_code = $customerCode;
            $sub->paystack_authorization_code = $authorizationCode;
            $sub->last_payment_ref = $reference;
            $sub->canceled_at = null;
            $sub->save();

            // 2) Create Paystack recurring subscription (charges next cycle automatically)
            // If already has subscription_code, don’t recreate
            if ($sub->paystack_subscription_code) {
                return;
            }

            $payload = [
                'customer' => $customerCode,
                'plan' => $plan->plan_code,
                'authorization' => $authorizationCode,
            ];

            // TRIAL NOTE:
            // Paystack supports start_date, but you still need authorization from a paid transaction.
            // If you want “14 days trial then first charge”, you’d need a separate trial flow.
            // For now: trial can exist BEFORE upgrade. Once user upgrades, billing starts.
            //
            // If you insist: you can set start_date = now()->addDays(14)->toIso8601String()
            // but the user has already paid once to authorize the card.

            $res = Http::withToken(config('paystack.secret_key'))
                ->post('https://api.paystack.co/subscription', $payload);

            if (!$res->ok() || !data_get($res->json(), 'status')) {
                abort(500, 'Failed to create Paystack subscription.');
            }

            $sub->paystack_subscription_code = (string) data_get($res->json(), 'data.subscription_code');
            $sub->paystack_email_token = (string) data_get($res->json(), 'data.email_token');
            $sub->save();
        });
    }

    private function extendFromCharge(int $tenantId, array $charge): void
    {
        $reference = (string) data_get($charge, 'reference', '');
        $customerCode = (string) data_get($charge, 'customer.customer_code', '');

        DB::transaction(function () use ($tenantId, $reference, $customerCode) {
            $sub = Subscription::where('tenant_id', $tenantId)->lockForUpdate()->first();
            if (!$sub) return;

            // Idempotency: avoid re-applying same charge
            if ($reference && $sub->last_payment_ref === $reference) return;

            $cycle = $sub->cycle ?: 'monthly';
            if (!isset($this->pricing[$cycle])) $cycle = 'monthly';

            $now = now();
            $base = $sub->expires_at ? Carbon::parse($sub->expires_at) : $now;
            if ($base->isPast()) $base = $now;

            $sub->expires_at = $base->copy()->addMonths($this->pricing[$cycle]['months']);
            $sub->plan = 'premium';
            $sub->provider = 'paystack';
            if ($customerCode && !$sub->paystack_customer_code) {
                $sub->paystack_customer_code = $customerCode;
            }
            if ($reference) $sub->last_payment_ref = $reference;
            $sub->save();
        });
    }
}

