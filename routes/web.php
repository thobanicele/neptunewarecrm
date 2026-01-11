<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TenantAdminController;
use App\Http\Controllers\TenantOnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/**
 * Onboarding: only on base domain (no tenant yet)
 */
// Route::middleware(['auth', \App\Http\Middleware\EnsureUserHasNoTenant::class])->group(function () {
//     Route::get('/onboarding/tenant', [TenantOnboardingController::class, 'create'])->name('tenant.onboarding.create');
//     Route::post('/onboarding/tenant', [TenantOnboardingController::class, 'store'])->name('tenant.onboarding.store');
// });
Route::middleware(['auth', 'no.tenant'])->group(function () {
    Route::get('/onboarding/tenant', [TenantOnboardingController::class, 'create'])
        ->name('tenant.onboarding.create');

    Route::post('/onboarding/tenant', [TenantOnboardingController::class, 'store'])
        ->name('tenant.onboarding.store');
});




/**
 * Tenant area: requires tenant context + auth
 * Access allowed for tenant roles.
 */
Route::middleware(['identify.tenant', 'tenant', 'auth', 'role:tenant_owner|tenant_admin|tenant_staff'])->group(function () {
    Route::get('/tenant/dashboard', [TenantAdminController::class, 'dashboard'])
        ->name('tenant.dashboard');
});

/**
 * Super admin area (base domain only)
 */
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/admin', function () {
        return 'Super Admin Panel'; // replace with controller later
    })->name('admin.dashboard');
});

require __DIR__ . '/auth.php';

