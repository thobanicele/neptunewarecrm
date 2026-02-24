<?php

namespace App\Http\Controllers;

use App\Models\BillingPlan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class BillingController extends Controller
{
    public function upgrade(string $tenant)
    {
        $tenant = app('tenant');

        $sub = Subscription::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan'     => $tenant->plan ?? config('plans.default_plan', 'free'),
                'provider' => 'paystack',
                'cycle'    => 'monthly',
                'status'   => 'inactive',
            ]
        );

        // ✅ all pricing (blade reads config directly, but keep for legacy)
        $pricing = config('plans.billing.pricing'); // ['premium'=>..., 'business'=>...]
        $paystack = [
            'currency' => config('plans.billing.currency', 'ZAR'),

            'premium_monthly_plan_code'  => config('plans.billing.paystack.premium_monthly_plan_code'),
            'premium_yearly_plan_code'   => config('plans.billing.paystack.premium_yearly_plan_code'),

            'business_monthly_plan_code' => config('plans.billing.paystack.business_monthly_plan_code'),
            'business_yearly_plan_code'  => config('plans.billing.paystack.business_yearly_plan_code'),
        ];

        $trialEnabled = (bool) config('plans.trial.enabled', true);
        $trialDays    = (int) config('plans.trial.days', 14);

        $trialEndsAt = $sub->trial_ends_at;
        $trialDaysLeft = $trialEndsAt ? max(0, now()->diffInDays($trialEndsAt, false)) : null;

        return view('tenant.billing.upgrade', compact(
            'tenant', 'sub', 'pricing', 'paystack', 'trialEnabled', 'trialDays', 'trialDaysLeft'
        ));
    }

    public function paystackInitialize(Request $request, string $tenant)
    {
        $tenant = app('tenant');

        $plan  = (string) $request->input('plan', 'premium');
        $cycle = (string) $request->input('cycle', 'monthly');

        abort_unless(in_array($plan, ['premium', 'business'], true), 400);
        abort_unless(in_array($cycle, ['monthly', 'yearly'], true), 400);

        $planCodeKey = "{$plan}_{$cycle}_plan_code"; // e.g. premium_monthly_plan_code
        $planCode = (string) config("plans.billing.paystack.$planCodeKey");

        if (empty($planCode)) {
            return back()->with('error', "Paystack plan code missing for {$plan} ({$cycle}). Please set env config.");
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
        $sub->plan  = $plan;
        $sub->save();

        // Paystack amount is in kobo (ZAR cents)
        $amount = (int) round((float) config("plans.billing.pricing.$plan.$cycle.amount", 0) * 100);

        $payload = [
            'email' => $email,
            'amount' => $amount,
            'plan' => $planCode,
            'callback_url' => tenant_route('tenant.billing.paystack.callback', ['tenant' => $tenant->subdomain]),
            'metadata' => [
                'tenant_id' => $tenant->id,
                'cycle' => $cycle,
                'plan' => $plan,
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
        if (!$reference) {
            return redirect()->route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain])
                ->with('error', 'Missing Paystack reference.');
        }

        $req = Http::withToken(config('services.paystack.secret'));
        if (app()->environment('local')) {
            $req = $req->withoutVerifying();
        }

        $verify = $req->get("https://api.paystack.co/transaction/verify/{$reference}")
            ->json();

        if (!data_get($verify, 'status') || data_get($verify, 'data.status') !== 'success') {
            return redirect()->route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain])
                ->with('error', 'Payment not successful.');
        }

        $plan = (string) data_get($verify, 'data.metadata.plan', 'premium');
        if (!in_array($plan, ['premium', 'business'], true)) $plan = 'premium';

        $tenant->plan = $plan;
        $tenant->save();

        Subscription::where('tenant_id', $tenant->id)->update([
            'status' => 'active',
            'starts_at' => now(),
            'plan' => $plan,
        ]);

        return redirect()->route('tenant.dashboard', ['tenant' => $tenant->subdomain])
            ->with('success', "Payment successful! {$plan} enabled.");
    }

    /**
     * NOTE: paystackWebhook() exists elsewhere but your active webhook route uses BillingWebhookController@handle.
     * Keep only one webhook handler to avoid double-processing.
     */
}

