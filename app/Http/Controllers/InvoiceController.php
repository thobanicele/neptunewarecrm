<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\TaxType;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use App\Services\DocumentNumberService;


class InvoiceController extends Controller
{
    public function index(string $tenantKey)
    {
        $tenant = app('tenant');

        $invoices = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->with(['company'])
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.invoices.index', compact('invoices'));
    }

    public function show(Tenant $tenant, Invoice $invoice)
    {
        abort_unless((int) $invoice->tenant_id === (int) $tenant->id, 404);

        $invoice->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'company.addresses.country',
            'company.addresses.subdivision',
            'contact',
            'salesPerson',
            'owner',
        ]);

        // 1) Prefer invoice snapshots (best: stays consistent even if company address changes later)
        $billTo = trim((string) ($invoice->billing_address_snapshot ?? ''));
        $shipTo = trim((string) ($invoice->shipping_address_snapshot ?? ''));

        // 2) Fallback to company defaults if snapshots are empty
        if (!$billTo || !$shipTo) {
            $billing = $invoice->company?->addresses
                ?->where('type', 'billing')
                ->sortByDesc('is_default_billing')
                ->sortByDesc('id')
                ->first();

            $shipping = $invoice->company?->addresses
                ?->where('type', 'shipping')
                ->sortByDesc('is_default_shipping')
                ->sortByDesc('id')
                ->first();

            if (!$billTo) {
                $billTo =
                    $billing?->toSnapshotString()
                    ?: $shipping?->toSnapshotString()
                    ?: '';
            }

            if (!$shipTo) {
                $shipTo =
                    $shipping?->toSnapshotString()
                    ?: $billing?->toSnapshotString()
                    ?: '';
            }
        }

        return view('tenant.invoices.show', compact('tenant', 'invoice', 'billTo', 'shipTo'));
    }

    public function create(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');

        // Feature gate
        if (!tenant_feature($tenant, 'invoicing_manual')) {
            return back()->with('error', 'Invoicing is not enabled for your plan.');
        }

        // Monthly cap (existing logic)
        $cap = \App\Support\TenantPlan::limit($tenant->plan, 'invoices.max_per_month', null);
        if (!is_null($cap)) {
            $countThisMonth = Invoice::query()
                ->where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            if ($countThisMonth >= (int) $cap) {
                return back()->with('error', "Invoice limit reached for this month ({$cap}). Upgrade to unlock more.");
            }
        }

        // ✅ Prefill from query string
        $prefillCompanyId = $request->integer('company_id') ?: null;
        $prefillContactId = $request->integer('contact_id') ?: null;

        // ✅ Validate prefilled company belongs to tenant
        if ($prefillCompanyId) {
            $ok = Company::query()
                ->where('tenant_id', $tenant->id)
                ->where('id', $prefillCompanyId)
                ->exists();

            if (!$ok) {
                $prefillCompanyId = null;
                $prefillContactId = null;
            }
        }

        // ✅ Validate contact belongs to tenant (+ company if provided)
        if ($prefillContactId) {
            $cq = Contact::query()
                ->where('tenant_id', $tenant->id)
                ->where('id', $prefillContactId);

            if ($prefillCompanyId) {
                $cq->where('company_id', $prefillCompanyId);
            }

            if (!$cq->exists()) {
                $prefillContactId = null;
            }
        }

        // ✅ Auto-pick first contact for company if only company provided
        if ($prefillCompanyId && !$prefillContactId) {
            $prefillContactId = Contact::query()
                ->where('tenant_id', $tenant->id)
                ->where('company_id', $prefillCompanyId)
                ->orderBy('name')
                ->value('id');
        }

        // Companies + address snapshots (same as you had)
        $companies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->with(['addresses.country', 'addresses.subdivision'])
            ->orderBy('name')
            ->get();

        $companiesJson = $companies->map(function ($c) {
            $billing = $c->addresses
                ->where('type', 'billing')
                ->sortByDesc('is_default_billing')
                ->sortByDesc('id')
                ->first();

            $shipping = $c->addresses
                ->where('type', 'shipping')
                ->sortByDesc('is_default_shipping')
                ->sortByDesc('id')
                ->first();

            return [
                'id' => $c->id,
                'name' => $c->name,
                'payment_terms' => $c->payment_terms,
                'vat_treatment' => $c->vat_treatment,
                'vat_number' => $c->vat_number,
                'billing_address' => $billing?->toSnapshotString(),
                'shipping_address' => $shipping?->toSnapshotString(),
                'address' => $billing?->toSnapshotString() ?: $shipping?->toSnapshotString(),
            ];
        })->keyBy('id');

        // ✅ Contacts list (filter to company when prefilled)
        $contacts = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->when($prefillCompanyId, fn ($q) => $q->where('company_id', $prefillCompanyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Users (sales people / owners)
        $users = User::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Products for picker
        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'description', 'unit_rate']);

        // Tax types (tenant + global)
        $taxTypes = TaxType::query()
            ->where(function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id)
                ->orWhereNull('tenant_id');
            })
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'rate', 'is_default']);

        $defaultTaxTypeId = $taxTypes->firstWhere('is_default', true)?->id ?? $taxTypes->first()?->id;

        return view('tenant.invoices.create', compact(
            'tenant',
            'companies',
            'companiesJson',
            'contacts',
            'users',
            'products',
            'taxTypes',
            'defaultTaxTypeId',
            'prefillCompanyId',
            'prefillContactId',
        ));
    }



    public function store(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');

        if (!tenant_feature($tenant, 'invoicing_manual')) {
            return back()->with('error', 'Invoicing is not enabled for your plan.');
        }

        // Monthly cap for free (or capped plans)
        $max = tenant_limit($tenant, 'invoices.max_per_month');
        if ($max !== null) {
            $count = Invoice::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            if ($count >= (int)$max) {
                return back()->with('error', "You've reached your monthly invoice limit ({$max}). Upgrade to Pro for unlimited invoices.")
                    ->withInput();
            }
        }

        $data = $request->validate([
            'deal_id'    => ['nullable','integer'],
            'company_id' => ['nullable','integer'],
            'contact_id' => ['nullable','integer'],

            'issued_at' => ['nullable','date'],
            'due_at'    => ['nullable','date'],

            'notes' => ['nullable','string'],
            'terms' => ['nullable','string'],

            'reference' => ['nullable','string','max:120'],

            'sales_person_user_id' => ['required','integer'],
            'tax_type_id' => ['nullable','integer'],

            'items' => ['required','array','min:1'],
            'items.*.product_id'  => ['nullable','integer'],
            'items.*.tax_type_id' => ['nullable','integer'],
            'items.*.sku'  => ['nullable','string','max:64'],
            'items.*.unit' => ['nullable','string','max:30'],
            'items.*.name' => ['required','string','max:190'],
            'items.*.description' => ['nullable','string'],
            'items.*.qty' => ['required','numeric','min:0.01'],
            'items.*.unit_price' => ['required','numeric','min:0'],
            'items.*.discount_pct' => ['nullable','numeric','min:0','max:100'],
        ]);

        // tenant safety
        if (!empty($data['deal_id']))    Deal::where('tenant_id', $tenant->id)->findOrFail((int)$data['deal_id']);
        if (!empty($data['company_id'])) Company::where('tenant_id', $tenant->id)->findOrFail((int)$data['company_id']);
        if (!empty($data['contact_id'])) Contact::where('tenant_id', $tenant->id)->findOrFail((int)$data['contact_id']);
        User::where('tenant_id', $tenant->id)->findOrFail((int)$data['sales_person_user_id']);

        return DB::transaction(function () use ($tenant, $data) {

            // Batch products
            $productIds = collect($data['items'])->pluck('product_id')->filter()->unique()->map(fn($id)=>(int)$id)->values();
            $productsById = collect();
            if ($productIds->isNotEmpty()) {
                $productsById = Product::where('tenant_id', $tenant->id)
                    ->whereIn('id', $productIds)
                    ->get(['id','sku','unit','name','description','unit_rate'])
                    ->keyBy('id');

                if ($productsById->count() !== $productIds->count()) abort(404);
            }

            // Tax types
            $defaultTaxTypeId = !empty($data['tax_type_id']) ? (int)$data['tax_type_id'] : null;
            $taxTypeIds = collect($data['items'])->pluck('tax_type_id')->filter()->push($defaultTaxTypeId)->filter()->unique()->map(fn($id)=>(int)$id)->values();
            $taxTypesById = collect();
            if ($taxTypeIds->isNotEmpty()) {
                $taxTypesById = TaxType::where('tenant_id', $tenant->id)
                    ->whereIn('id', $taxTypeIds)
                    ->get(['id','name','rate'])
                    ->keyBy('id');

                if ($taxTypesById->count() !== $taxTypeIds->count()) abort(404);
            }

            $invoiceNumber = app(DocumentNumberService::class)->nextInvoiceNumber($tenant->id);

            $snapshotItems = [];

            $subtotalGross = 0.0;
            $discountTotal = 0.0;
            $taxTotal      = 0.0;

            foreach (array_values($data['items']) as $pos => $i) {
                $qty       = (float)$i['qty'];
                $productId = !empty($i['product_id']) ? (int)$i['product_id'] : null;
                $taxTypeId = !empty($i['tax_type_id']) ? (int)$i['tax_type_id'] : $defaultTaxTypeId;

                $sku  = $i['sku'] ?? null;
                $unit = $i['unit'] ?? null;

                $name      = $i['name'];
                $desc      = $i['description'] ?? null;
                $unitPrice = (float)$i['unit_price'];

                $discountPct = isset($i['discount_pct']) ? (float)$i['discount_pct'] : 0.0;
                $discountPct = max(0.0, min(100.0, $discountPct));

                if ($productId) {
                    $p = $productsById->get($productId);
                    if (!$p) abort(404);
                    $sku  = $p->sku;
                    $unit = $p->unit;
                    $name = $p->name;
                    $desc = $p->description;
                    $unitPrice = (float)$p->unit_rate;
                }

                $grossLine = round($qty * $unitPrice, 2);
                $discAmt   = round($grossLine * ($discountPct / 100), 2);
                $netLine   = round($grossLine - $discAmt, 2);

                $subtotalGross += $grossLine;
                $discountTotal += $discAmt;

                $taxName = null;
                $taxRate = 0.0;
                if ($taxTypeId) {
                    $t = $taxTypesById->get($taxTypeId);
                    if (!$t) abort(404);
                    $taxName = $t->name;
                    $taxRate = (float)$t->rate;
                }

                $lineTax = round($netLine * ($taxRate / 100), 2);
                $taxTotal += $lineTax;

                $snapshotItems[] = [
                    'tenant_id'   => $tenant->id,
                    'product_id'  => $productId,
                    'sku'         => $sku,
                    'unit'        => $unit,
                    'tax_type_id' => $taxTypeId,
                    'position'    => $pos,
                    'name'        => $name,
                    'description' => $desc,
                    'qty'         => $qty,
                    'unit_price'  => $unitPrice,
                    'discount_pct'    => $discountPct,
                    'discount_amount' => $discAmt,
                    'tax_rate'    => $taxRate,
                    'tax_name'    => $taxName,
                    'line_total'  => $netLine,
                    'tax_amount'  => $lineTax,
                ];
            }

            $subtotalGross = round($subtotalGross, 2);
            $discountTotal = round($discountTotal, 2);
            $taxTotal      = round($taxTotal, 2);

            $netSubtotal   = round($subtotalGross - $discountTotal, 2);
            $total         = round($netSubtotal + $taxTotal, 2);

            $effectiveRate = $netSubtotal > 0 ? round(($taxTotal / $netSubtotal) * 100, 2) : 0;

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,

                'deal_id'   => $data['deal_id'] ?? null,
                'company_id'=> $data['company_id'] ?? null,
                'contact_id'=> $data['contact_id'] ?? null,

                'owner_user_id'        => auth()->id(),
                'sales_person_user_id' => (int)$data['sales_person_user_id'],

                'invoice_number' => $invoiceNumber,
                'status' => 'draft',
                'issued_at' => $data['issued_at'] ?? now()->toDateString(),
                'due_at'    => $data['due_at'] ?? null,

                'reference' => $data['reference'] ?? null,

                'tax_type_id' => $data['tax_type_id'] ?? null,
                'tax_rate'    => $effectiveRate,

                'subtotal'        => $subtotalGross,
                'discount_amount' => $discountTotal,
                'tax_amount'      => $taxTotal,
                'total'           => $total,

                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            foreach ($snapshotItems as $item) {
                $invoice->items()->create($item);
            }

            return redirect()->to(tenant_route('tenant.invoices.show', $invoice))
                ->with('success', 'Invoice created.');
        });
    }

    public function edit(string $tenantKey, Invoice $invoice)
    {
        $tenant = app('tenant');
        abort_unless($invoice->tenant_id === $tenant->id, 404);

        return view('tenant.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, string $tenantKey, Invoice $invoice)
    {
        $tenant = app('tenant');
        abort_unless($invoice->tenant_id === $tenant->id, 404);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Issued invoices cannot be edited.');
        }

        $data = $request->validate([
            'reference' => ['nullable','string','max:120'],
        ]);

        $invoice->update($data);

        return back()->with('success', 'Invoice updated.');
    }

    public function issue(string $tenantKey, Invoice $invoice)
    {
        $tenant = app('tenant');
        abort_unless((int)$invoice->tenant_id === (int)$tenant->id, 404);

        if ($invoice->status === 'issued') {
            return back()->with('success', 'This invoice is already issued and locked.');
        }

        if ($invoice->status === 'paid') {
            return back()->with('error', 'This invoice is already marked as paid and cannot be re-issued.');
        }

        if ($invoice->status !== 'draft') {
            return back()->with('error', "Only draft invoices can be issued. Current status: {$invoice->status}.");
        }

        // SARS-friendly: invoice must be tied to a customer (company)
        if (!$invoice->company_id) {
            return back()->with('error', 'Please select a company/customer before issuing the invoice.');
        }

        // Items required
        if (!$invoice->items()->exists()) {
            return back()->with('error', 'Cannot issue an invoice with no items. Please add at least one line item.');
        }

        // Ensure issued date
        $issuedAt = $invoice->issued_at ?: now()->toDateString();

        $invoice->update([
            'status'    => 'issued',
            'issued_at' => $issuedAt,
        ]);

        return back()->with('success', 'Invoice issued successfully. It is now locked for editing.');
    }


    public function markPaid(string $tenantKey, Invoice $invoice)
    {
        $tenant = app('tenant');
        abort_unless((int)$invoice->tenant_id === (int)$tenant->id, 404);

        // Pro-only (as per your plan spec)
        if (!tenant_feature($tenant, 'invoice_email_send')) {
            return back()->with('error', 'Payment tracking is available on the Pro plan. Upgrade to enable paid status, statements and exports.');
        }

        if ($invoice->status === 'draft') {
            return back()->with('error', 'Please issue the invoice before marking it as paid.');
        }

        if ($invoice->status === 'paid') {
            $when = $invoice->paid_at ? $invoice->paid_at->format('d/m/Y H:i') : null;
            return back()->with('success', 'Invoice is already marked as paid' . ($when ? " (Paid at: {$when})." : '.') );
        }

        if ($invoice->status !== 'issued') {
            return back()->with('error', "Only issued invoices can be marked as paid. Current status: {$invoice->status}.");
        }

        $invoice->update([
            'status'  => 'paid',
            'paid_at' => $invoice->paid_at ?? now(),
        ]);

        return back()->with('success', 'Invoice marked as paid.');
    }

}
