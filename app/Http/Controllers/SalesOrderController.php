<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\TaxType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\ActivityLogger;

class SalesOrderController extends Controller
{
    public function index(Request $request, string $tenant)
    {
        $tenant = app('tenant');
        $this->authorize('viewAny', SalesOrder::class);

        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');
        $sales_person_user_id = (string) $request->get('sales_person_user_id', '');

        $sort = (string) $request->get('sort', 'created_at');
        $dir  = strtolower((string) $request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'sales_order_number',
            'status',
            'subtotal',
            'total',
            'issued_at',
            'created_at',
            'company',
            'sales_person',
        ];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $query = SalesOrder::query()
            ->where('sales_orders.tenant_id', $tenant->id)
            ->with(['company', 'contact', 'deal', 'salesPerson']);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('sales_order_number', 'like', "%{$q}%")
                    ->orWhere('quote_number', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('sales_orders.status', $status);
        }

        if ($sales_person_user_id !== '') {
            $query->where('sales_orders.sales_person_user_id', $sales_person_user_id);
        }

        if ($sort === 'company') {
            $query->leftJoin('companies', function ($join) use ($tenant) {
                    $join->on('companies.id', '=', 'sales_orders.company_id')
                         ->where('companies.tenant_id', '=', $tenant->id);
                })
                ->select('sales_orders.*')
                ->orderBy('companies.name', $dir);
        } elseif ($sort === 'sales_person') {
            $query->leftJoin('users as sp', function ($join) use ($tenant) {
                    $join->on('sp.id', '=', 'sales_orders.sales_person_user_id')
                         ->where('sp.tenant_id', '=', $tenant->id);
                })
                ->select('sales_orders.*')
                ->orderBy('sp.name', $dir);
        } else {
            $query->orderBy("sales_orders.$sort", $dir);
        }

        $query->orderByDesc('sales_orders.id');

        $items = $query->paginate(25)->withQueryString();

        $salesPeople = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.sales_orders.index', compact(
            'tenant',
            'items',
            'q',
            'status',
            'sales_person_user_id',
            'salesPeople',
            'sort',
            'dir',
        ));
    }

    public function create(Request $request, string $tenantKey)
    {
        $tenant = app('tenant');
        $this->authorize('create', SalesOrder::class);

        // ✅ Prefill from query string
        $prefillCompanyId = $request->integer('company_id') ?: null;
        $prefillContactId = $request->integer('contact_id') ?: null;

        // Validate prefilled company belongs to tenant
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

        // Validate contact belongs to tenant (+ company if possible)
        if ($prefillContactId) {
            $cq = Contact::query()
                ->where('tenant_id', $tenant->id)
                ->where('id', $prefillContactId);

            if ($prefillCompanyId && Schema::hasColumn('contacts', 'company_id')) {
                $cq->where('company_id', $prefillCompanyId);
            }

            if (!$cq->exists()) {
                $prefillContactId = null;
            }
        }

        // Auto-pick first contact for company if only company provided
        if ($prefillCompanyId && !$prefillContactId && Schema::hasColumn('contacts', 'company_id')) {
            $prefillContactId = Contact::query()
                ->where('tenant_id', $tenant->id)
                ->where('company_id', $prefillCompanyId)
                ->orderBy('name')
                ->value('id');
        }

        // Companies + addresses for snapshots
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

        $contacts = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->when(
                $prefillCompanyId && Schema::hasColumn('contacts', 'company_id'),
                fn($q) => $q->where('company_id', $prefillCompanyId)
            )
            ->orderBy('name')
            ->get(['id','name','email']);

        $deals = Deal::query()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get(['id','title','company_id','primary_contact_id']);

        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id','name','sku','description','unit_rate','unit']);

        $taxTypes = TaxType::query()
            ->where(function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id');
            })
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id','name','rate','is_default']);

        $defaultTaxTypeId = $taxTypes->firstWhere('is_default', true)?->id ?? $taxTypes->first()?->id;

        $salesPeople = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id','name']);

        return view('tenant.sales_orders.create', compact(
            'tenant',
            'companies',
            'companiesJson',
            'contacts',
            'deals',
            'products',
            'taxTypes',
            'defaultTaxTypeId',
            'salesPeople',
            'prefillCompanyId',
            'prefillContactId',
        ));
    }

    public function edit(string $tenantKey, SalesOrder $salesOrder)
    {
        $tenant = app('tenant');
        abort_unless((int)$salesOrder->tenant_id === (int)$tenant->id, 404);
        $this->authorize('update', $salesOrder);

        $salesOrder->load([
            'items' => fn($q) => $q->orderBy('position'),
            'company.addresses.country',
            'company.addresses.subdivision',
            'contact',
            'deal',
            'salesPerson',
            'owner',
        ]);

        // Reuse same datasets as create()
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

        $contacts = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id','name','email']);

        $deals = Deal::query()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get(['id','title','company_id','primary_contact_id']);

        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id','name','sku','description','unit_rate','unit']);

        $taxTypes = TaxType::query()
            ->where(function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id');
            })
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id','name','rate','is_default']);

        $defaultTaxTypeId = $taxTypes->firstWhere('is_default', true)?->id ?? $taxTypes->first()?->id;

        $salesPeople = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id','name']);

        return view('tenant.sales_orders.edit', compact(
            'tenant',
            'salesOrder',
            'companies',
            'companiesJson',
            'contacts',
            'deals',
            'products',
            'taxTypes',
            'defaultTaxTypeId',
            'salesPeople',
        ));
    }

    public function store(Request $request, string $tenant)
    {
        $tenant = app('tenant');
        $this->authorize('create', SalesOrder::class);

        // ✅ monthly cap for Free (10/month)
        $max = tenant_limit($tenant, 'sales_orders.max_per_month');
        if ($max === null) {
            $planKey = $tenant->plan ?? $tenant->plan_key ?? config('plans.default_plan', 'free');
            $max = data_get(config("plans.plans.$planKey"), 'sales_orders.max_per_month');
        }

        if ($max !== null) {
            $count = SalesOrder::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            if ($count >= (int) $max) {
                return back()
                    ->with('error', "You've reached your monthly Sales Orders limit ({$max}). Upgrade to create more.")
                    ->withInput();
            }
        }

        $data = $request->validate([
            'company_id' => ['required', 'integer'],
            'contact_id' => ['nullable', 'integer'],
            'deal_id'    => ['nullable', 'integer'],
            'issued_at'  => ['nullable', 'date'],
            'currency'   => ['nullable', 'string', 'max:10'],
            'reference'  => ['nullable', 'string', 'max:120'],
            'notes'      => ['nullable', 'string'],
            'terms'      => ['nullable', 'string'],

            'items'                   => ['required', 'array', 'min:1'],
            'items.*.name'            => ['required', 'string', 'max:190'],
            'items.*.description'     => ['nullable', 'string'],
            'items.*.qty'             => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'        => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_name'        => ['nullable', 'string', 'max:100'],
            'items.*.tax_amount'      => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total'      => ['nullable', 'numeric', 'min:0'],
            'items.*.sku'             => ['nullable', 'string', 'max:64'],
            'items.*.unit'            => ['nullable', 'string', 'max:30'],
            'items.*.product_id'      => ['nullable', 'integer'],
            'items.*.tax_type_id'     => ['nullable', 'integer'],
            'items.*.discount_pct'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // ✅ tenant safety + load company with addresses (needed for snapshots)
        $company = Company::query()
            ->where('tenant_id', $tenant->id)
            ->with(['addresses.country', 'addresses.subdivision'])
            ->findOrFail((int) $data['company_id']);

        if (!empty($data['contact_id'])) {
            Contact::where('tenant_id', $tenant->id)->findOrFail((int) $data['contact_id']);
        }
        if (!empty($data['deal_id'])) {
            Deal::where('tenant_id', $tenant->id)->findOrFail((int) $data['deal_id']);
        }

        // ✅ build snapshots (same fallback order you use elsewhere)
        $billing = $company->addresses
            ->where('type', 'billing')
            ->sortByDesc('is_default_billing')
            ->sortByDesc('id')
            ->first();

        $shipping = $company->addresses
            ->where('type', 'shipping')
            ->sortByDesc('is_default_shipping')
            ->sortByDesc('id')
            ->first();

        $billSnap = $billing?->toSnapshotString()
            ?: $shipping?->toSnapshotString()
            ?: '';

        $shipSnap = $shipping?->toSnapshotString()
            ?: $billing?->toSnapshotString()
            ?: '';

        $billingId = $billing?->id;
        $shippingId = $shipping?->id;

        return DB::transaction(function () use ($tenant, $data, $billSnap, $shipSnap, $billingId, $shippingId) {

            $soNumber = $this->generateSalesOrderNumber($tenant->id);

            $subtotal = 0; $taxAmount = 0; $discount = 0;
            foreach ($data['items'] as $it) {
                $line = (float) ($it['line_total'] ?? ((float)$it['qty'] * (float)$it['unit_price']));
                $tax  = (float) ($it['tax_amount'] ?? 0);
                $subtotal += $line;
                $taxAmount += $tax;
                $discount += (float) ($it['discount_amount'] ?? 0);
            }
            $total = max(0, ($subtotal - $discount) + $taxAmount);

            $salesOrder = SalesOrder::create([
                'tenant_id' => $tenant->id,
                'sales_order_number' => $soNumber,

                'company_id' => $data['company_id'],
                'contact_id' => $data['contact_id'] ?? null,
                'deal_id'    => $data['deal_id'] ?? null,

                'owner_user_id'        => auth()->id(),
                'sales_person_user_id' => auth()->id(),

                'status'    => 'draft',
                'issued_at' => $data['issued_at'] ?? now()->toDateString(),
                'currency'  => $data['currency'] ?? 'ZAR',
                'reference' => $data['reference'] ?? null,

                'subtotal'        => round($subtotal, 2),
                'discount_amount' => round($discount, 2),
                'tax_amount'      => round($taxAmount, 2),
                'total'           => round($total, 2),

                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,

                // ✅ snapshots
                'billing_address_id' => $billingId,
                'shipping_address_id' => $shippingId,
                'billing_address_snapshot' => $billSnap,
                'shipping_address_snapshot' => $shipSnap,
            ]);

            foreach ($data['items'] as $idx => $it) {
                $salesOrder->items()->create([
                    'tenant_id' => $tenant->id,
                    'position'  => $idx,

                    'product_id'  => $it['product_id'] ?? null,
                    'tax_type_id' => $it['tax_type_id'] ?? null,
                    'sku'         => $it['sku'] ?? null,
                    'unit'        => $it['unit'] ?? null,

                    'name'        => $it['name'],
                    'description' => $it['description'] ?? null,
                    'qty'         => (float) $it['qty'],
                    'unit_price'  => (float) $it['unit_price'],

                    'discount_pct'    => (float) ($it['discount_pct'] ?? 0),
                    'discount_amount' => (float) ($it['discount_amount'] ?? 0),

                    'tax_name'    => $it['tax_name'] ?? null,
                    'tax_rate'    => (float) ($it['tax_rate'] ?? 0),
                    'line_total'  => (float) ($it['line_total'] ?? ((float)$it['qty'] * (float)$it['unit_price'])),
                    'tax_amount'  => (float) ($it['tax_amount'] ?? 0),
                ]);
            }

            app(ActivityLogger::class)->log($tenant->id, 'sales_order.created', $salesOrder, [
                'sales_order_number' => $salesOrder->sales_order_number,
                'source' => 'manual',
                'total' => (float) $salesOrder->total,
                'currency' => $salesOrder->currency,
            ]);

            return redirect()
                ->to(tenant_route('tenant.sales-orders.show', ['salesOrder' => $salesOrder->id]))
                ->with('success', 'Sales Order created.');
        });
    }

   

    public function update(Request $request, string $tenant, SalesOrder $salesOrder)
    {
        $tenant = app('tenant');
        abort_unless((int) $salesOrder->tenant_id === (int) $tenant->id, 404);
        $this->authorize('update', $salesOrder);

        if ($salesOrder->status === 'converted') {
            return back()->with('error', 'Converted Sales Orders cannot be edited.');
        }

        $data = $request->validate([
            'company_id' => ['required', 'integer'],
            'contact_id' => ['nullable', 'integer'],
            'deal_id'    => ['nullable', 'integer'],
            'issued_at'  => ['nullable', 'date'],
            'currency'   => ['nullable', 'string', 'max:10'],
            'reference'  => ['nullable', 'string', 'max:120'],
            'notes'      => ['nullable', 'string'],
            'terms'      => ['nullable', 'string'],

            'items'                   => ['required', 'array', 'min:1'],
            'items.*.name'            => ['required', 'string', 'max:190'],
            'items.*.description'     => ['nullable', 'string'],
            'items.*.qty'             => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'        => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_name'        => ['nullable', 'string', 'max:100'],
            'items.*.tax_amount'      => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total'      => ['nullable', 'numeric', 'min:0'],
            'items.*.sku'             => ['nullable', 'string', 'max:64'],
            'items.*.unit'            => ['nullable', 'string', 'max:30'],
            'items.*.product_id'      => ['nullable', 'integer'],
            'items.*.tax_type_id'     => ['nullable', 'integer'],
            'items.*.discount_pct'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // ✅ tenant safety + load company with addresses for snapshots
        $company = Company::query()
            ->where('tenant_id', $tenant->id)
            ->with(['addresses.country', 'addresses.subdivision'])
            ->findOrFail((int) $data['company_id']);

        if (!empty($data['contact_id'])) {
            Contact::where('tenant_id', $tenant->id)->findOrFail((int) $data['contact_id']);
        }
        if (!empty($data['deal_id'])) {
            Deal::where('tenant_id', $tenant->id)->findOrFail((int) $data['deal_id']);
        }

        // ✅ build snapshots from latest company defaults
        $billing = $company->addresses
            ->where('type', 'billing')
            ->sortByDesc('is_default_billing')
            ->sortByDesc('id')
            ->first();

        $shipping = $company->addresses
            ->where('type', 'shipping')
            ->sortByDesc('is_default_shipping')
            ->sortByDesc('id')
            ->first();

        $billSnap = $billing?->toSnapshotString()
            ?: $shipping?->toSnapshotString()
            ?: '';

        $shipSnap = $shipping?->toSnapshotString()
            ?: $billing?->toSnapshotString()
            ?: '';

        $billingId = $billing?->id;
        $shippingId = $shipping?->id;

        return DB::transaction(function () use (
            $tenant,
            $salesOrder,
            $data,
            $billSnap,
            $shipSnap,
            $billingId,
            $shippingId
        ) {
            $oldTotal = (float) $salesOrder->total;

            $subtotal = 0; $taxAmount = 0; $discount = 0;
            foreach ($data['items'] as $it) {
                $line = (float) ($it['line_total'] ?? ((float)$it['qty'] * (float)$it['unit_price']));
                $tax  = (float) ($it['tax_amount'] ?? 0);
                $subtotal += $line;
                $taxAmount += $tax;
                $discount += (float) ($it['discount_amount'] ?? 0);
            }
            $total = max(0, ($subtotal - $discount) + $taxAmount);

            $salesOrder->forceFill([
                'company_id' => $data['company_id'],
                'contact_id' => $data['contact_id'] ?? null,
                'deal_id'    => $data['deal_id'] ?? null,

                'issued_at'  => $data['issued_at'] ?? $salesOrder->issued_at,
                'currency'   => $data['currency'] ?? $salesOrder->currency,
                'reference'  => $data['reference'] ?? null,

                'subtotal'        => round($subtotal, 2),
                'discount_amount' => round($discount, 2),
                'tax_amount'      => round($taxAmount, 2),
                'total'           => round($total, 2),

                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,

                // ✅ refresh snapshots on update
                'billing_address_id' => $billingId,
                'shipping_address_id' => $shippingId,
                'billing_address_snapshot' => $billSnap,
                'shipping_address_snapshot' => $shipSnap,
            ])->save();

            // rewrite items (simple + consistent)
            $salesOrder->items()->delete();

            foreach ($data['items'] as $idx => $it) {
                $salesOrder->items()->create([
                    'tenant_id' => $tenant->id,
                    'position'  => $idx,

                    'product_id'  => $it['product_id'] ?? null,
                    'tax_type_id' => $it['tax_type_id'] ?? null,
                    'sku'         => $it['sku'] ?? null,
                    'unit'        => $it['unit'] ?? null,

                    'name'        => $it['name'],
                    'description' => $it['description'] ?? null,
                    'qty'         => (float) $it['qty'],
                    'unit_price'  => (float) $it['unit_price'],

                    'discount_pct'    => (float) ($it['discount_pct'] ?? 0),
                    'discount_amount' => (float) ($it['discount_amount'] ?? 0),

                    'tax_name'    => $it['tax_name'] ?? null,
                    'tax_rate'    => (float) ($it['tax_rate'] ?? 0),
                    'line_total'  => (float) ($it['line_total'] ?? ((float)$it['qty'] * (float)$it['unit_price'])),
                    'tax_amount'  => (float) ($it['tax_amount'] ?? 0),
                ]);
            }

            app(ActivityLogger::class)->log($tenant->id, 'sales_order.updated', $salesOrder, [
                'sales_order_number' => $salesOrder->sales_order_number,
                'old_total' => $oldTotal,
                'new_total' => (float) $salesOrder->total,
            ]);

            return redirect()
                ->to(tenant_route('tenant.sales-orders.show', ['salesOrder' => $salesOrder->id]))
                ->with('success', 'Sales Order updated.');
        });
    }
    

    public function show(string $tenant, SalesOrder $salesOrder)
    {
        $tenant = app('tenant');
        abort_unless((int) $salesOrder->tenant_id === (int) $tenant->id, 404);
        $this->authorize('view', $salesOrder);

        $salesOrder->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'company.addresses.country',
            'company.addresses.subdivision',
            'contact',
            'deal',
            'quote',
            'activityLogs.user',
        ]);

        // 1) Prefer SO snapshots
        $billTo = trim((string) ($salesOrder->billing_address_snapshot ?? ''));
        $shipTo = trim((string) ($salesOrder->shipping_address_snapshot ?? ''));

        // 2) Fallback to company defaults if snapshots empty
        if (!$billTo || !$shipTo) {
            $billing = $salesOrder->company?->addresses
                ?->where('type', 'billing')
                ->sortByDesc('is_default_billing')
                ->sortByDesc('id')
                ->first();

            $shipping = $salesOrder->company?->addresses
                ?->where('type', 'shipping')
                ->sortByDesc('is_default_shipping')
                ->sortByDesc('id')
                ->first();

            if (!$billTo) {
                $billTo = $billing?->toSnapshotString()
                    ?: $shipping?->toSnapshotString()
                    ?: '';
            }

            if (!$shipTo) {
                $shipTo = $shipping?->toSnapshotString()
                    ?: $billing?->toSnapshotString()
                    ?: '';
            }
        }

        return view('tenant.sales_orders.show', compact('tenant', 'salesOrder', 'billTo', 'shipTo'));
    }

    public function issue(string $tenant, SalesOrder $salesOrder)
    {
        $tenant = app('tenant');
        abort_unless((int) $salesOrder->tenant_id === (int) $tenant->id, 404);
        $this->authorize('update', $salesOrder);

        if ($salesOrder->status !== 'draft') {
            return back()->with('error', 'Only draft sales orders can be issued.');
        }

        $old = (string) $salesOrder->status;

        $salesOrder->forceFill([
            'status' => 'issued',
            'issued_at' => $salesOrder->issued_at ?: now()->toDateString(),
        ])->save();

        app(ActivityLogger::class)->log($tenant->id, 'sales_order.status_changed', $salesOrder, [
            'sales_order_number' => $salesOrder->sales_order_number,
            'from' => $old,
            'to'   => 'issued',
        ]);

        return back()->with('success', 'Sales Order issued.');
    }

    public function cancel(string $tenant, SalesOrder $salesOrder)
    {
        $tenant = app('tenant');
        abort_unless((int) $salesOrder->tenant_id === (int) $tenant->id, 404);
        $this->authorize('update', $salesOrder);

        if ($salesOrder->status === 'converted') {
            return back()->with('error', 'This sales order is already converted to an invoice.');
        }

        $old = (string) $salesOrder->status;

        $salesOrder->forceFill(['status' => 'cancelled'])->save();

        app(ActivityLogger::class)->log($tenant->id, 'sales_order.status_changed', $salesOrder, [
            'sales_order_number' => $salesOrder->sales_order_number,
            'from' => $old,
            'to'   => 'cancelled',
        ]);

        return back()->with('success', 'Sales Order cancelled.');
    }

    public function export(Request $request, string $tenant)
    {
        $tenant = app('tenant');
        $this->authorize('export', SalesOrder::class);

        if (!tenant_feature($tenant, 'export')) {
            return back()->with('error', 'Export to Excel is available on the Premium plan.');
        }

        $rows = SalesOrder::query()
            ->where('tenant_id', $tenant->id)
            ->with(['company'])
            ->latest()
            ->limit(5000)
            ->get()
            ->map(function ($so) {
                return [
                    'Sales Order #' => $so->sales_order_number,
                    'Status'        => $so->status,
                    'Quote #'       => $so->quote_number,
                    'Reference'     => $so->reference,
                    'Customer'      => $so->company?->name,
                    'Currency'      => $so->currency,
                    'Subtotal'      => (string) $so->subtotal,
                    'Discount'      => (string) $so->discount_amount,
                    'Tax'           => (string) $so->tax_amount,
                    'Total'         => (string) $so->total,
                    'Issued At'     => (string) $so->issued_at,
                    'Created At'    => (string) $so->created_at,
                ];
            });

        $filename = 'sales-orders-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_keys($rows->first() ?? ['Sales Order #' => '']));
            foreach ($rows as $r) fputcsv($out, $r);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Quote -> Sales Order
     */
    public function convertFromQuote(string $tenantKey, Quote $quote)
    {
        $tenant = app('tenant');
        abort_unless((int) $quote->tenant_id === (int) $tenant->id, 404);
        $this->authorize('update', $quote);

        if (strtolower((string) $quote->status) !== 'accepted') {
            return back()->with('error', 'Only accepted quotes can be converted to a sales order.');
        }

        // monthly cap
        $max = tenant_limit($tenant, 'sales_orders.max_per_month');
        if ($max === null) {
            $planKey = $tenant->plan ?? $tenant->plan_key ?? config('plans.default_plan', 'free');
            $max = data_get(config("plans.plans.$planKey"), 'sales_orders.max_per_month');
        }

        if ($max !== null) {
            $countThisMonth = SalesOrder::where('tenant_id', $tenant->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            if ($countThisMonth >= (int) $max) {
                return back()->with('error', "Sales Orders limit reached ({$max}/month). Upgrade to create more.");
            }
        }

        $quote->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'company.addresses.country',
            'company.addresses.subdivision',
            'contact',
            'deal',
        ]);

        if (!$quote->company_id) {
            return back()->with('error', 'This quote has no company/customer.');
        }

        if ($quote->items->isEmpty()) {
            return back()->with('error', 'This quote has no items.');
        }

        $existing = SalesOrder::where('tenant_id', $tenant->id)
            ->where('quote_id', $quote->id)
            ->first();

        if ($existing) {
            return redirect()
                ->to(tenant_route('tenant.sales-orders.show', ['salesOrder' => $existing->id]))
                ->with('success', 'A sales order already exists for this quote. Redirected.');
        }

        // ✅ Build address snapshots from company defaults
        $company = $quote->company;

        $billing = $company?->addresses
            ?->where('type', 'billing')
            ->sortByDesc('is_default_billing')
            ->sortByDesc('id')
            ->first();

        $shipping = $company?->addresses
            ?->where('type', 'shipping')
            ->sortByDesc('is_default_shipping')
            ->sortByDesc('id')
            ->first();

        $billSnap = $billing?->toSnapshotString()
            ?: $shipping?->toSnapshotString()
            ?: '';

        $shipSnap = $shipping?->toSnapshotString()
            ?: $billing?->toSnapshotString()
            ?: '';

        $billingId = $billing?->id;
        $shippingId = $shipping?->id;

        return DB::transaction(function () use ($tenant, $quote, $billSnap, $shipSnap, $billingId, $shippingId) {
            $soNumber = $this->generateSalesOrderNumber($tenant->id);

            $salesOrder = SalesOrder::create([
                'tenant_id' => $tenant->id,

                'sales_order_number' => $soNumber,
                'quote_id'           => $quote->id,
                'quote_number'       => $quote->quote_number,
                'reference'          => $quote->quote_number,

                'deal_id'    => $quote->deal_id,
                'company_id' => $quote->company_id,
                'contact_id' => $quote->contact_id,

                'owner_user_id'        => $quote->owner_user_id,
                'sales_person_user_id' => $quote->sales_person_user_id,

                'status'    => 'draft',
                'issued_at' => now()->toDateString(),
                'due_at'    => null,

                'currency'  => $quote->currency ?? 'ZAR',

                'subtotal'        => (float) ($quote->subtotal ?? 0),
                'discount_amount' => (float) ($quote->discount_amount ?? 0),
                'tax_rate'        => (float) ($quote->tax_rate ?? 0),
                'tax_amount'      => (float) ($quote->tax_amount ?? 0),
                'total'           => (float) ($quote->total ?? 0),

                'notes' => $quote->notes,
                'terms' => $quote->terms,

                // ✅ snapshots
                'billing_address_id' => $billingId,
                'shipping_address_id' => $shippingId,
                'billing_address_snapshot' => $billSnap,
                'shipping_address_snapshot' => $shipSnap,
            ]);

            foreach ($quote->items as $it) {
                $salesOrder->items()->create([
                    'tenant_id'   => $tenant->id,
                    'product_id'  => $it->product_id ?? null,
                    'tax_type_id' => $it->tax_type_id ?? null,
                    'position'    => $it->position ?? 0,

                    'sku'         => $it->sku ?? null,
                    'unit'        => $it->unit ?? null,

                    'name'        => $it->name,
                    'description' => $it->description,

                    'qty'         => (float) $it->qty,
                    'unit_price'  => (float) $it->unit_price,

                    'discount_pct'    => (float) ($it->discount_pct ?? 0),
                    'discount_amount' => (float) ($it->discount_amount ?? 0),

                    'tax_name'    => $it->tax_name ?? null,
                    'tax_rate'    => (float) ($it->tax_rate ?? 0),

                    'line_total'  => (float) $it->line_total,
                    'tax_amount'  => (float) $it->tax_amount,
                ]);
            }

            app(ActivityLogger::class)->log($tenant->id, 'sales_order.created_from_quote', $salesOrder, [
                'sales_order_number' => $salesOrder->sales_order_number,
                'quote_id' => $quote->id,
                'quote_number' => $quote->quote_number,
            ]);

            app(ActivityLogger::class)->log($tenant->id, 'quote.converted_to_sales_order', $quote, [
                'quote_number' => $quote->quote_number,
                'sales_order_id' => $salesOrder->id,
                'sales_order_number' => $salesOrder->sales_order_number,
            ]);

            return redirect()
                ->to(tenant_route('tenant.sales-orders.show', ['salesOrder' => $salesOrder->id]))
                ->with('success', 'Sales Order created from accepted quote (Draft).');
        });
    }

    /**
     * Sales Order -> Invoice
     */
    public function convertToInvoice(string $tenantKey, SalesOrder $salesOrder)
    {
        $tenant = app('tenant');
        abort_unless((int) $salesOrder->tenant_id === (int) $tenant->id, 404);
        $this->authorize('update', $salesOrder);

        if (!tenant_feature($tenant, 'invoicing_convert_from_quote')) {
            return back()->with('error', 'Sales Order → Invoice conversion is available on the Premium plan.');
        }

        $salesOrder->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'company.addresses.country',
            'company.addresses.subdivision',
            'contact',
            'deal',
            'quote',
        ]);

        if (!$salesOrder->company_id) {
            return back()->with('error', 'This sales order has no company/customer.');
        }

        if ($salesOrder->items->isEmpty()) {
            return back()->with('error', 'This sales order has no items.');
        }

        $existing = Invoice::where('tenant_id', $tenant->id)
            ->where('sales_order_id', $salesOrder->id)
            ->first();

        if ($existing) {
            return redirect()
                ->to(tenant_route('tenant.invoices.show', ['invoice' => $existing->id]))
                ->with('success', 'An invoice already exists for this sales order. Redirected.');
        }

        // ✅ Prefer SO snapshots, fallback to company defaults
        $billSnap = trim((string) ($salesOrder->billing_address_snapshot ?? ''));
        $shipSnap = trim((string) ($salesOrder->shipping_address_snapshot ?? ''));

        $billingId = $salesOrder->billing_address_id ?? null;
        $shippingId = $salesOrder->shipping_address_id ?? null;

        if ($billSnap === '' || $shipSnap === '') {
            $company = $salesOrder->company;

            $billing = $company?->addresses
                ?->where('type', 'billing')
                ->sortByDesc('is_default_billing')
                ->sortByDesc('id')
                ->first();

            $shipping = $company?->addresses
                ?->where('type', 'shipping')
                ->sortByDesc('is_default_shipping')
                ->sortByDesc('id')
                ->first();

            if ($billSnap === '') {
                $billSnap = $billing?->toSnapshotString()
                    ?: $shipping?->toSnapshotString()
                    ?: '';
                $billingId = $billingId ?: $billing?->id;
            }

            if ($shipSnap === '') {
                $shipSnap = $shipping?->toSnapshotString()
                    ?: $billing?->toSnapshotString()
                    ?: '';
                $shippingId = $shippingId ?: $shipping?->id;
            }
        }

        return DB::transaction(function () use ($tenant, $salesOrder, $billSnap, $shipSnap, $billingId, $shippingId) {
            $invoiceNumber = $this->generateInvoiceNumber($tenant->id);

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'invoice_number' => $invoiceNumber,

                'sales_order_id'     => $salesOrder->id,
                'sales_order_number' => $salesOrder->sales_order_number,

                'quote_id'     => $salesOrder->quote_id,
                'quote_number' => $salesOrder->quote_number,

                'reference'    => $salesOrder->reference,

                'deal_id'    => $salesOrder->deal_id,
                'company_id' => $salesOrder->company_id,
                'contact_id' => $salesOrder->contact_id,

                'owner_user_id'        => $salesOrder->owner_user_id,
                'sales_person_user_id' => $salesOrder->sales_person_user_id,

                'status'    => 'draft',
                'issued_at' => now()->toDateString(),
                'due_at'    => null,

                'currency'  => $salesOrder->currency ?? 'ZAR',

                'subtotal'        => (float) ($salesOrder->subtotal ?? 0),
                'discount_amount' => (float) ($salesOrder->discount_amount ?? 0),
                'tax_rate'        => (float) ($salesOrder->tax_rate ?? 0),
                'tax_amount'      => (float) ($salesOrder->tax_amount ?? 0),
                'total'           => (float) ($salesOrder->total ?? 0),

                'notes' => $salesOrder->notes,
                'terms' => $salesOrder->terms,

                // ✅ snapshots on invoice
                'billing_address_id' => $billingId,
                'shipping_address_id' => $shippingId,
                'billing_address_snapshot' => $billSnap,
                'shipping_address_snapshot' => $shipSnap,
            ]);

            foreach ($salesOrder->items as $it) {
                $invoice->items()->create([
                    'tenant_id'   => $tenant->id,
                    'product_id'  => $it->product_id ?? null,
                    'tax_type_id' => $it->tax_type_id ?? null,
                    'position'    => $it->position ?? 0,

                    'sku'         => $it->sku ?? null,
                    'unit'        => $it->unit ?? null,

                    'name'        => $it->name,
                    'description' => $it->description,

                    'qty'         => (float) $it->qty,
                    'unit_price'  => (float) $it->unit_price,

                    'discount_pct'    => (float) ($it->discount_pct ?? 0),
                    'discount_amount' => (float) ($it->discount_amount ?? 0),

                    'tax_name'    => $it->tax_name ?? null,
                    'tax_rate'    => (float) ($it->tax_rate ?? 0),

                    'line_total'  => (float) $it->line_total,
                    'tax_amount'  => (float) $it->tax_amount,
                ]);
            }

            $old = (string) $salesOrder->status;
            $salesOrder->forceFill(['status' => 'converted'])->save();

            app(ActivityLogger::class)->log($tenant->id, 'sales_order.converted_to_invoice', $salesOrder, [
                'sales_order_number' => $salesOrder->sales_order_number,
                'from' => $old,
                'to' => 'converted',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            app(ActivityLogger::class)->log($tenant->id, 'invoice.created_from_sales_order', $invoice, [
                'invoice_number' => $invoice->invoice_number,
                'sales_order_id' => $salesOrder->id,
                'sales_order_number' => $salesOrder->sales_order_number,
            ]);

            return redirect()
                ->to(tenant_route('tenant.invoices.show', ['invoice' => $invoice->id]))
                ->with('success', 'Invoice created from Sales Order (Draft).');
        });
    }

    public function reopen(string $tenant, SalesOrder $salesOrder)
    {
        $tenant = app('tenant');
        abort_unless((int) $salesOrder->tenant_id === (int) $tenant->id, 404);
        $this->authorize('update', $salesOrder);

        if (strtolower((string) $salesOrder->status) !== 'cancelled') {
            return back()->with('error', 'Only cancelled sales orders can be reopened.');
        }

        return DB::transaction(function () use ($tenant, $salesOrder) {
            $old = (string) $salesOrder->status;

            $salesOrder->forceFill([
                'status' => 'issued',
                'issued_at' => $salesOrder->issued_at ?: now()->toDateString(),
            ])->save();

            app(ActivityLogger::class)->log($tenant->id, 'sales_order.status_changed', $salesOrder, [
                'sales_order_number' => $salesOrder->sales_order_number,
                'from' => $old,
                'to'   => 'issued',
                'note' => 'Reopened from cancelled.',
            ]);

            return back()->with('success', 'Sales Order reopened and set back to Issued.');
        });
    }

    protected function generateSalesOrderNumber(int $tenantId): string
    {
        $next = (int) SalesOrder::where('tenant_id', $tenantId)->max('id') + 1;
        return 'SO-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    protected function generateInvoiceNumber(int $tenantId): string
    {
        if (class_exists(\App\Services\DocumentNumberService::class)) {
            return app(\App\Services\DocumentNumberService::class)->nextInvoiceNumber($tenantId);
        }

        $next = (int) Invoice::where('tenant_id', $tenantId)->max('id') + 1;
        return 'INV-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}


