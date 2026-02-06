<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TenantOnboardingController;
use App\Http\Controllers\TenantDashboardController;
use App\Http\Controllers\TenantSettingsController;

use App\Http\Controllers\DealController;
use App\Http\Controllers\DealExportController;

use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadLifecycleController;

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CompanyAddressController;
use App\Http\Controllers\GeoController;

use App\Http\Controllers\BillingController;
use App\Http\Controllers\ActivityController;

use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuotePdfController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TaxTypeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\CustomerStatementController;
use App\Http\Controllers\InvoiceEmailController;
use App\Http\Controllers\CompanyStatementController;
use App\Http\Controllers\CompanyContactsController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CreditNoteRefundController;

/*
|--------------------------------------------------------------------------
| Home / Landing
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| App Entry (decide where to send user)
|--------------------------------------------------------------------------
*/
Route::get('/app', function () {
    $user = auth()->user();

    if ($user->hasRole('super_admin')) {
        return redirect()->route('admin.dashboard');
    }

    if (!$user->tenant_id) {
        return redirect()->route('tenant.onboarding.create');
    }

    // {tenant:subdomain} route model binding accepts model instance
    return redirect()->route('tenant.dashboard', ['tenant' => $user->tenant]);
})->middleware('auth')->name('app.home');

/*
|--------------------------------------------------------------------------
| Tenant Onboarding (no tenant in URL)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'no.tenant'])->group(function () {
    Route::get('/onboarding/tenant', [TenantOnboardingController::class, 'create'])
        ->name('tenant.onboarding.create');

    Route::post('/onboarding/tenant', [TenantOnboardingController::class, 'store'])
        ->name('tenant.onboarding.store');
});

/*
|--------------------------------------------------------------------------
| Path-based Tenant Area
| URL: /t/{tenant:subdomain}
|--------------------------------------------------------------------------
*/
Route::prefix('t/{tenant:subdomain}')
    ->middleware([
        'auth',
        'identify.tenant.path',
        'role:super_admin|tenant_owner|tenant_admin|tenant_staff',
    ])
    // ✅ IMPORTANT: remove scopeBindings() unless your Tenant model has
    // contacts()/companies()/deals() relationships for scoping.
    // ->scopeBindings()
    ->group(function () {

        /*
        | Dashboard
        */
        Route::get('/dashboard', [TenantDashboardController::class, 'index'])
            ->name('tenant.dashboard');

        /*
        | Leads (Contacts filtered by lifecycle_stage = lead)
        */
        Route::get('/leads', [LeadController::class, 'index'])->name('tenant.leads.index');
        Route::get('/leads/kanban', [LeadController::class, 'kanban'])->name('tenant.leads.kanban');

        Route::get('/leads/create', [LeadController::class, 'create'])->name('tenant.leads.create');
        Route::post('/leads', [LeadController::class, 'store'])->name('tenant.leads.store');

        Route::get('/leads/{contact}/edit', [LeadController::class, 'edit'])->name('tenant.leads.edit');
        Route::put('/leads/{contact}', [LeadController::class, 'update'])->name('tenant.leads.update');
        Route::delete('/leads/{contact}', [LeadController::class, 'destroy'])->name('tenant.leads.destroy');

        // Lead lifecycle actions (kanban / qualify)
        Route::patch('/leads/{contact}/stage', [LeadLifecycleController::class, 'updateStage'])->name('tenant.leads.stage');
        Route::post('/leads/{contact}/qualify', [LeadLifecycleController::class, 'qualify'])->name('tenant.leads.qualify');

        /*
        | Companies / Contacts
        */
        Route::resource('/companies', CompanyController::class)->names('tenant.companies');
        Route::resource('/contacts', ContactController::class)->names('tenant.contacts');

        Route::get('/companies/{company}/addresses', [CompanyAddressController::class, 'index'])
            ->name('tenant.companies.addresses.index');

        Route::post('/companies/{company}/addresses', [CompanyAddressController::class, 'store'])
            ->name('tenant.companies.addresses.store');

        Route::post('/companies/{company}/addresses/{address}/default', [CompanyAddressController::class, 'setDefault'])
            ->name('tenant.companies.addresses.default');

        Route::get('/geo/subdivisions/{countryIso2}', [GeoController::class, 'subdivisions'])
            ->name('tenant.geo.subdivisions');


        /*
        | Billing / Upgrade (placeholder)
        */
        Route::get('/billing/upgrade', [BillingController::class, 'upgrade'])
            ->name('tenant.billing.upgrade');

        /*
        | Deals Kanban
        */
        Route::get('/deals/kanban', [DealController::class, 'kanban'])
            ->name('tenant.deals.kanban');

        Route::patch('/deals/{deal}/stage', [DealController::class, 'updateStage'])
            ->name('tenant.deals.updateStage');

        Route::post('/deals/{deal}/activities', [DealController::class, 'storeActivity'])
            ->name('tenant.deals.activities.store');

        Route::post('/deals/{deal}/notes', [DealController::class, 'addNote'])
            ->name('tenant.deals.notes.store');

        /*
        | Deals Export (optional)
        | ✅ If you got "Class DealExportController does not exist", comment this route too.
        */
        Route::get('/deals/export', [DealExportController::class, 'export'])
            ->middleware('tenant.limits:feature.export')
            ->name('tenant.deals.export');

        /*
        |Activities Routes
        */
        Route::post('activities', [ActivityController::class, 'store'])->name('tenant.activities.store');
        Route::patch('activities/{activity}/toggle', [ActivityController::class, 'toggleDone'])
            ->name('tenant.activities.toggle');

        Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])
            ->name('tenant.activities.destroy');

        Route::get('activities/followups', [ActivityController::class, 'followups'])
            ->name('tenant.activities.followups');
        /*
        |Quotes
        */
        Route::resource('quotes', QuoteController::class)->names('tenant.quotes');;

        Route::get('quotes/{quote}/pdf', [QuotePdfController::class, 'stream'])
            ->name('tenant.quotes.pdf.stream');

        Route::get('quotes/{quote}/pdf/download', [QuotePdfController::class, 'download'])
            ->name('tenant.quotes.pdf.download');

        Route::post('quotes/{quote}/mark-sent', [QuoteController::class, 'markSent'])
            ->name('tenant.quotes.markSent');

        Route::post('quotes/{quote}/accept', [QuoteController::class, 'accept'])
            ->name('tenant.quotes.accept');

        Route::post('quotes/{quote}/decline', [QuoteController::class, 'decline'])
            ->name('tenant.quotes.decline');
        
        Route::post('quotes/{quote}/convert-to-invoice', [QuoteController::class, 'convertToInvoice'])
            ->name('tenant.quotes.convertToInvoice');

        Route::get('companies/{company}/contacts', [CompanyContactsController::class, 'index'])
            ->name('tenant.companies.contacts.index');

        Route::resource('invoices', InvoiceController::class)->names('tenant.invoices');

        Route::get('invoices/{invoice}/pdf', [InvoicePdfController::class, 'stream'])
            ->name('tenant.invoices.pdf.stream');

        Route::get('invoices/{invoice}/pdf/download', [InvoicePdfController::class, 'download'])
            ->name('tenant.invoices.pdf.download');


        // Actions
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])
            ->name('tenant.invoices.issue');

        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])
            ->name('tenant.invoices.markPaid');

        // Statements / Export (Pro)
        Route::get('invoices/statement', [CustomerStatementController::class, 'index'])
            ->name('tenant.invoices.statement');

        Route::get('invoices/statement/download', [CustomerStatementController::class, 'download'])
            ->name('tenant.invoices.statement.download');

        Route::post('invoices/{invoice}/send-email', [InvoiceEmailController::class, 'send'])
            ->name('tenant.invoices.sendEmail');

        Route::resource('payments', PaymentController::class)
            ->only(['index','create','store','show']);

        Route::resource('credit-notes', CreditNoteController::class)
            ->only(['index','create','store','show']);

        // Credit Note Refunds
        Route::get('credit-notes/{creditNote}/refund', [CreditNoteRefundController::class, 'create'])
            ->name('tenant.credit_notes.refund.create');
        Route::post('credit-notes/{creditNote}/refund', [CreditNoteRefundController::class, 'store'])
            ->name('tenant.credit_notes.refund.store');

        // Ledger-style statement (company)
        Route::get('companies/{company}/statement', [CompanyStatementController::class, 'show'])
            ->name('tenant.companies.statement');



        Route::resource('payments', PaymentController::class);
        Route::resource('credit-notes', CreditNoteController::class);


        // routes/tenant.php (or wherever your tenant routes are)
        Route::get('reports/statement', [CustomerStatementController::class, 'index'])
            ->name('tenant.reports.statement');

        Route::get('reports/statement/pdf', [CustomerStatementController::class, 'pdf'])
            ->name('tenant.reports.statement.pdf');

        Route::get('reports/statement/csv', [CustomerStatementController::class, 'csv'])
            ->name('tenant.reports.statement.csv');
        /*
        |Company Statement (inside company profile)
        */
        Route::get('companies/{company}/statement', [CompanyStatementController::class, 'show'])
            ->name('tenant.companies.statement');

        Route::get('companies/{company}/statement/pdf', [CompanyStatementController::class, 'pdf'])
            ->name('tenant.companies.statement.pdf');

        Route::get('companies/{company}/statement/csv', [CompanyStatementController::class, 'csv'])
            ->name('tenant.companies.statement.csv');

        Route::post('companies/{company}/statement/email', [CompanyStatementController::class, 'email'])
            ->name('tenant.companies.statement.email');

        /*
        |Products
        */  
        Route::resource('products', ProductController::class)->names('tenant.products');

        /*
        | Settings
        */
        Route::get('/settings', [TenantSettingsController::class, 'edit'])
            ->middleware('role:super_admin|tenant_owner|tenant_admin')
            ->name('tenant.settings.edit');

        Route::put('/settings', [TenantSettingsController::class, 'update'])
            ->middleware([
                'role:super_admin|tenant_owner|tenant_admin',
                'tenant.limits:feature.custom_branding',
            ])
            ->name('tenant.settings.update');

        /*
        |Tax Types
        */
        Route::resource('tax-types', TaxTypeController::class)
            ->names('tenant.tax-types')
            ->except(['show']); // optional

        Route::post('tax-types/{taxType}/default', [TaxTypeController::class, 'makeDefault'])
            ->name('tenant.tax-types.default');

        Route::post('tax-types/{taxType}/toggle', [TaxTypeController::class, 'toggleActive'])
            ->name('tenant.tax-types.toggle');
        /*
        | Deals (FULL CRUD)
        */
        Route::resource('/deals', DealController::class)->names([
            'index'   => 'tenant.deals.index',
            'create'  => 'tenant.deals.create',
            'store'   => 'tenant.deals.store',
            'show'    => 'tenant.deals.show',
            'edit'    => 'tenant.deals.edit',
            'update'  => 'tenant.deals.update',
            'destroy' => 'tenant.deals.destroy',
        ]);
    });

/*
|--------------------------------------------------------------------------
| Super Admin (base domain only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/admin', function () {
        return 'Super Admin Panel';
    })->name('admin.dashboard');
});

require __DIR__ . '/auth.php';
