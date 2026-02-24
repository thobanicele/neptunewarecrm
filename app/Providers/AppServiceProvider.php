<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use App\Models\Activity;
use App\Support\TenantPlan;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Deal::class => \App\Policies\DealPolicy::class,
        \App\Models\Contact::class => \App\Policies\ContactPolicy::class,
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
        \App\Models\Quote::class => \App\Policies\QuotePolicy::class,
        \App\Models\SalesOrder::class => \App\Policies\SalesOrderPolicy::class,
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\Payment::class => \App\Policies\PaymentPolicy::class,
        \App\Models\CreditNote::class => \App\Policies\CreditNotePolicy::class,
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
        \App\Models\Brand::class => \App\Policies\BrandPolicy::class,
        \App\Models\Category::class => \App\Policies\CategoryPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        Blade::if('feature', fn(string $f) => TenantPlan::currentFeature($f));
        Schema::defaultStringLength(191);

        View::composer('layouts.backend.part.sidebar', function ($view) {
            // ✅ tenant-safe: sidebar may render on non-tenant routes (/login, /email/verify, etc.)
            $tenant = app()->bound('tenant') ? app('tenant') : null;

            $count = 0;

            if ($tenant) {
                // Optional: cache for 30s to avoid a query on every page load
                $count = Cache::remember("tenant:{$tenant->id}:overdue_followups", 30, function () use ($tenant) {
                    return Activity::query()
                        ->where('tenant_id', $tenant->id)
                        ->whereNull('done_at')
                        ->whereNotNull('due_at')
                        ->where('due_at', '<', now())
                        ->count();
                });
            }

            $view->with('overdueFollowupsCount', $count);
        });
    }
}
