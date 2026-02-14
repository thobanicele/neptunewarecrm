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
use App\Http\Controllers\CreditNotePdfController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TenantUserController;

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

    if (!$user || $user->is_active === false) {
        auth()->logout();
        return redirect()->route('login')->with('error', 'Your account is inactive.');
    }

    if ($user->hasRole('super_admin')) {
        return redirect()->route('admin.dashboard');
    }

    if (!$user->tenant_id) {
        return redirect()->route('tenant.onboarding.create');
    }

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
        'active.user', 
        'identify.tenant.path',
        'set.permission.tenant',
        'role:super_admin|tenant_owner|tenant_admin|tenant_staff',
    ])
    // ✅ IMPORTANT: remove scopeBindings() unless your Tenant model has
    // contacts()/companies()/deals() relationships for scoping.
    // ->scopeBindings()
    ->group(function () {

        /*
        |Tenant User Permissions
        */
        Route::middleware(['role:tenant_owner|tenant_admin|super_admin'])->group(function () {
            Route::get('settings/users', [TenantUserController::class, 'index'])
                ->name('tenant.settings.users.index');

            Route::post('settings/users/invite', [TenantUserController::class, 'invite'])
                ->name('tenant.settings.users.invite');

            Route::patch('settings/users/{user}/role', [TenantUserController::class, 'updateRole'])
                ->name('tenant.settings.users.role');

            Route::patch('settings/users/{user}/deactivate', [TenantUserController::class, 'deactivate'])
                ->name('tenant.settings.users.deactivate');

            Route::delete('settings/users/{user}', [TenantUserController::class, 'destroy'])
                ->name('tenant.settings.users.destroy');
        });
        
        // Public invite accept link (works before tenant membership)
        Route::get('/invites/accept/{token}', [TenantUserController::class, 'accept'])
            ->name('tenant.invites.accept');


        /*
        | Dashboard
        */
        Route::get('dashboard', [DashboardController::class, 'index'])
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

        /*
        |Leads Export Premium-feature
        */
        Route::get('leads/export', [LeadController::class, 'export'])
            ->name('tenant.leads.export');


        // Lead lifecycle actions (kanban / qualify)
        Route::patch('/leads/{contact}/stage', [LeadLifecycleController::class, 'updateStage'])->name('tenant.leads.stage');
        Route::post('/leads/{contact}/qualify', [LeadLifecycleController::class, 'qualify'])->name('tenant.leads.qualify');

        /*
        |Contacts Export Premium-feature
        */
        Route::get('contacts/export', [ContactController::class, 'export'])
            ->name('tenant.contacts.export');

        /*
        |Caompanies Export Premium-feature
        */
        Route::get('companies/export', [CompanyController::class, 'export'])
            ->name('tenant.companies.export');

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
        Route::get('billing/upgrade', [BillingController::class, 'upgrade'])
            ->name('tenant.billing.upgrade');

        Route::post('billing/paystack/initialize', [BillingController::class, 'paystackInitialize'])
            ->name('tenant.billing.paystack.initialize');

        Route::get('billing/paystack/callback', [BillingController::class, 'paystackCallback'])
            ->name('tenant.billing.paystack.callback');

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
        |Activities Export Premium-feature
        */
        Route::get('activities/followups/export', [ActivityController::class, 'followupsExport'])
            ->name('tenant.activities.followups.export');
        
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
        /*
        |Quotes Export Pro-feature
        */
        Route::get('quotes/export', [QuoteController::class, 'export'])
            ->name('tenant.quotes.export');
            
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
        /*
        |InvoicesExport Premium-feature
        */
        Route::get('invoices/export', [InvoiceController::class, 'export'])
            ->name('tenant.invoices.export');
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

        /*
        |Payment Exports Premium-feature
        */
        Route::get('payments/export', [PaymentController::class, 'export'])
            ->name('tenant.payments.export');

        Route::resource('payments', PaymentController::class)->names('tenant.payments');

        Route::get('payments', [PaymentController::class, 'index'])->name('tenant.payments.index');
        Route::get('payments/{payment}/allocate', [PaymentController::class, 'allocateForm'])
            ->name('tenant.payments.allocate.form');

        Route::post('payments/{payment}/allocate', [PaymentController::class, 'allocateStore'])
            ->name('tenant.payments.allocate.store');

        Route::delete('payments/{payment}/allocations/{allocation}', [PaymentController::class, 'allocationDestroy'])
            ->name('tenant.payments.allocations.destroy');

        Route::delete('payments/{payment}/allocations', [PaymentController::class, 'allocationsReset'])
            ->name('tenant.payments.allocations.reset');

        /*
        |Credit Notes Export Premium-feature
        */
        Route::get('credit-notes/export', [CreditNoteController::class, 'export'])
            ->name('tenant.credit-notes.export');

        Route::resource('credit-notes', CreditNoteController::class)
            ->names('tenant.credit-notes');

        Route::get('credit-notes', [CreditNoteController::class, 'index'])->name('tenant.credit-notes.index');

        // PDF (same pattern as invoice/quote)
        Route::get('credit-notes/{credit_note}/pdf', [CreditNotePdfController::class, 'stream'])
            ->name('tenant.credit-notes.pdf.stream');

        Route::get('credit-notes/{credit_note}/pdf/download', [CreditNotePdfController::class, 'download'])
            ->name('tenant.credit-notes.pdf.download');

        // Credit Note Refunds
        Route::get('credit-notes/{creditNote}/refund', [CreditNoteRefundController::class, 'create'])
            ->name('tenant.credit_notes.refund.create');
        Route::post('credit-notes/{creditNote}/refund', [CreditNoteRefundController::class, 'store'])
            ->name('tenant.credit_notes.refund.store');

        // Ledger-style statement (company)
        Route::get('companies/{company}/statement', [CompanyStatementController::class, 'show'])
            ->name('tenant.companies.statement');


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
            
        Route::get('companies/{company}/open-invoices', [PaymentController::class, 'openInvoices'])
            ->name('tenant.companies.openInvoices');


        /*
        |Products
        */  
        Route::get('products/export', [ProductController::class, 'export'])
            ->name('tenant.products.export');
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

// webhook (outside auth, Paystack server calls this)
Route::post('/paystack/webhook', [BillingWebhookController::class, 'handle'])
    ->name('paystack.webhook');

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/admin', function () {
        return 'Super Admin Panel';
    })->name('admin.dashboard');
});

require __DIR__ . '/auth.php';
