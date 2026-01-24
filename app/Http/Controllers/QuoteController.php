<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteSequence;
use App\Models\Deal;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $sales_person_user_id = $request->query('sales_person_user_id');

        $items = Quote::query()
            ->where('tenant_id', $tenant->id)
            ->with(['deal','company','contact','salesPerson','owner'])
            ->when($status, fn ($qry) => $qry->where('status', $status))
            ->when($sales_person_user_id, fn ($qry) => $qry->where('sales_person_user_id', $sales_person_user_id))
            ->when($q, function ($qry) use ($q) {
                $qry->where(function ($x) use ($q) {
                    $x->where('quote_number', 'like', "%{$q}%")
                      ->orWhere('notes', 'like', "%{$q}%")
                      ->orWhereHas('deal', fn ($d) => $d->where('title', 'like', "%{$q}%"))
                      ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                      ->orWhereHas('contact', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $salesPeople = User::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id','name']);

        return view('tenant.quotes.index', compact(
            'tenant','items','salesPeople','q','status','sales_person_user_id'
        ));
    }

    public function create(Request $request)
    {
        $tenant = app('tenant');

        $deal = null;
        $dealId = $request->query('deal_id');

        if ($dealId) {
            $deal = Deal::query()
                ->where('tenant_id', $tenant->id)
                ->with(['company', 'primaryContact'])
                ->findOrFail($dealId);
        }

        $salesPeople = User::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $prefillSalesPersonId = $deal?->owner_user_id ?? auth()->id();

        $companies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'address',
                'billing_address',
                'shipping_address',
                'vat_treatment',
                'vat_number',
                'payment_terms',      // ✅ NEW
            ]);

        $contacts = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $prefillCompanyId = $deal?->company_id;
        $prefillContactId = $deal?->primary_contact_id; // ensure deals table has this

        $deals = Deal::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'company_id', 'primary_contact_id']); // ✅ include these

        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id','sku','unit','name','description','unit_rate']);


        $taxTypes = \App\Models\TaxType::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'rate', 'is_default']);

        $defaultTaxTypeId = $taxTypes->firstWhere('is_default', true)?->id ?? $taxTypes->first()?->id;

        return view('tenant.quotes.create', compact(
            'tenant',
            'deal',
            'deals',
            'companies',
            'contacts',
            'prefillCompanyId',
            'prefillContactId',
            'salesPeople',
            'prefillSalesPersonId',
            'products',
            'taxTypes',
            'defaultTaxTypeId'
        ));
    }


    public function store(Request $request)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'deal_id'    => ['nullable','integer'],
            'company_id' => ['nullable','integer'],
            'contact_id' => ['nullable','integer'],

            'issued_at'     => ['nullable','date'],
            'valid_until'   => ['nullable','date'],
            'notes'         => ['nullable','string'],
            'terms'         => ['nullable','string'],
            'status'        => ['nullable','in:draft,sent,accepted,declined,expired'],

            // ✅ NEW: customer reference (from frontend)
            'customer_reference' => ['nullable','string','max:120'],

            'sales_person_user_id' => ['required','integer'],

            // header default (fallback)
            'tax_type_id' => ['nullable','integer'],

            'items' => ['required','array','min:1'],
            'items.*.product_id'  => ['nullable','integer'],
            'items.*.tax_type_id' => ['nullable','integer'],

            // snapshots (optional)
            'items.*.sku'  => ['nullable','string','max:64'],
            // you removed UNIT from UI - keep nullable so it doesn't break
            'items.*.unit' => ['nullable','string','max:30'],

            'items.*.name'        => ['required','string','max:190'],
            'items.*.description' => ['nullable','string'],
            'items.*.qty'         => ['required','numeric','min:0.01'],
            'items.*.unit_price'  => ['required','numeric','min:0'],

            // ✅ NEW: per-line discount %
            'items.*.discount_pct' => ['nullable','numeric','min:0','max:100'],
        ]);

        // Tenant safety (header fields)
        if (!empty($data['deal_id'])) {
            Deal::where('tenant_id', $tenant->id)->findOrFail((int) $data['deal_id']);
        }
        if (!empty($data['company_id'])) {
            Company::where('tenant_id', $tenant->id)->findOrFail((int) $data['company_id']);
        }
        if (!empty($data['contact_id'])) {
            Contact::where('tenant_id', $tenant->id)->findOrFail((int) $data['contact_id']);
        }
        User::where('tenant_id', $tenant->id)->findOrFail((int) $data['sales_person_user_id']);

        return DB::transaction(function () use ($tenant, $data) {

            // --------- Batch load Products ----------
            $productIds = collect($data['items'])
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values();

            $productsById = collect();
            if ($productIds->isNotEmpty()) {
                $productsById = Product::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('id', $productIds)
                    ->get(['id','sku','unit','name','description','unit_rate'])
                    ->keyBy('id');

                if ($productsById->count() !== $productIds->count()) {
                    abort(404);
                }
            }

            // --------- Batch load Tax Types ----------
            $defaultTaxTypeId = !empty($data['tax_type_id']) ? (int) $data['tax_type_id'] : null;

            $taxTypeIds = collect($data['items'])
                ->pluck('tax_type_id')
                ->filter()
                ->push($defaultTaxTypeId)
                ->filter()
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values();

            $taxTypesById = collect();
            if ($taxTypeIds->isNotEmpty()) {
                $taxTypesById = \App\Models\TaxType::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('id', $taxTypeIds)
                    ->get(['id','name','rate'])
                    ->keyBy('id');

                if ($taxTypesById->count() !== $taxTypeIds->count()) {
                    abort(404);
                }
            }

            $quoteNumber = $this->nextQuoteNumber($tenant->id);

            // Build snapshot items + compute totals server-side
            $snapshotItems = [];

            $subtotalGross   = 0.0; // sum(qty*rate) BEFORE discount
            $discountTotal   = 0.0; // sum(discount amounts)
            $taxTotal        = 0.0; // VAT on NET (after discount)

            foreach (array_values($data['items']) as $pos => $i) {

                $qty       = (float) $i['qty'];
                $productId = !empty($i['product_id']) ? (int) $i['product_id'] : null;
                $taxTypeId = !empty($i['tax_type_id']) ? (int) $i['tax_type_id'] : $defaultTaxTypeId;

                // defaults from post (snapshots)
                $sku  = $i['sku'] ?? null;
                $unit = $i['unit'] ?? null;

                $name      = $i['name'];
                $desc      = $i['description'] ?? null;
                $unitPrice = (float) $i['unit_price'];

                // ✅ discount %
                $discountPct = isset($i['discount_pct']) ? (float) $i['discount_pct'] : 0.0;
                $discountPct = max(0.0, min(100.0, $discountPct));

                // ✅ snapshot from product if selected
                if ($productId) {
                    $p = $productsById->get($productId);
                    if (!$p) abort(404);

                    $sku  = $p->sku;
                    $unit = $p->unit; // ok even if UI removed it

                    $name      = $p->name;
                    $desc      = $p->description;
                    $unitPrice = (float) $p->unit_rate;
                }

                $grossLine = round($qty * $unitPrice, 2);
                $discAmt   = round($grossLine * ($discountPct / 100), 2);
                $netLine   = round($grossLine - $discAmt, 2);

                $subtotalGross += $grossLine;
                $discountTotal += $discAmt;

                // tax snapshot
                $taxName = null;
                $taxRate = 0.0;

                if ($taxTypeId) {
                    $t = $taxTypesById->get($taxTypeId);
                    if (!$t) abort(404);

                    $taxName = $t->name;
                    $taxRate = (float) $t->rate;
                }

                // ✅ VAT on NET amount
                $lineTax = round($netLine * ($taxRate / 100), 2);
                $taxTotal += $lineTax;

                $snapshotItems[] = [
                    'tenant_id'   => $tenant->id,
                    'product_id'  => $productId,
                    'tax_type_id' => $taxTypeId,
                    'position'    => $pos,

                    // snapshots
                    'sku'         => $sku,
                    'unit'        => $unit,

                    'name'        => $name,
                    'description' => $desc,
                    'qty'         => $qty,
                    'unit_price'  => $unitPrice,

                    // ✅ discount snapshot (requires DB cols on quote_items)
                    'discount_pct'    => $discountPct,
                    'discount_amount' => $discAmt,

                    // store net line as line_total (excl VAT)
                    'line_total'  => $netLine,

                    // VAT snapshot
                    'tax_name'    => $taxName,
                    'tax_rate'    => $taxRate,
                    'tax_amount'  => $lineTax,
                ];
            }

            $subtotalGross = round($subtotalGross, 2);
            $discountTotal = round($discountTotal, 2);
            $taxTotal      = round($taxTotal, 2);

            $netSubtotal = round($subtotalGross - $discountTotal, 2);
            $total       = round($netSubtotal + $taxTotal, 2);

            // effective VAT rate against net subtotal
            $effectiveRate = $netSubtotal > 0 ? round(($taxTotal / $netSubtotal) * 100, 2) : 0;

            $quote = Quote::create([
                'tenant_id' => $tenant->id,
                'deal_id'   => $data['deal_id'] ?? null,
                'company_id'=> $data['company_id'] ?? null,
                'contact_id'=> $data['contact_id'] ?? null,

                'owner_user_id'       => auth()->id(),
                'sales_person_user_id'=> (int) $data['sales_person_user_id'],

                'quote_number' => $quoteNumber,
                'status'       => $data['status'] ?? 'draft',
                'issued_at'    => $data['issued_at'] ?? now()->toDateString(),
                'valid_until'  => $data['valid_until'] ?? null,

                // ✅ NEW: customer reference
                'customer_reference' => $data['customer_reference'] ?? null,

                'tax_rate'   => $effectiveRate,

                // Keep subtotal as GROSS (so you can show discount separately)
                'subtotal'   => $subtotalGross,

                // ✅ NEW: quote-level discount total (requires DB col on quotes)
                'discount_amount' => $discountTotal,

                'tax_amount' => $taxTotal,
                'total'      => $total,

                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            foreach ($snapshotItems as $item) {
                $quote->items()->create($item);
            }

            return redirect()
                ->to(tenant_route('tenant.quotes.show', ['quote' => $quote->id]))
                ->with('success', 'Quote created.');
        });
    }




    public function show(\App\Models\Tenant $tenant, Quote $quote)
    {
        $tenant = app('tenant');
        abort_unless((int)$quote->tenant_id === (int)$tenant->id, 404);

        $quote->load([
            'items' => function ($q) {
                $q->orderBy('position');
            },
            'company',
            'contact',
            'deal',
            'salesPerson',
        ]);

        return view('tenant.quotes.show', compact('tenant', 'quote'));
    }


    public function edit(\App\Models\Tenant $tenant, Quote $quote)
    {
        $tenant = app('tenant');
        abort_unless((int) $quote->tenant_id === (int) $tenant->id, 404);

        $quote->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'deal',
            'company',
            'contact',
            'salesPerson',
            'owner',
        ]);

        $deals = Deal::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'company_id', 'primary_contact_id']);

        $companies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'address',
                'billing_address',
                'shipping_address',
                'vat_treatment',
                'vat_number',
                'payment_terms', // ✅ NEW
            ]);

        $contacts = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $salesPeople = User::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'description', 'unit_rate']);

        $taxTypes = \App\Models\TaxType::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'rate', 'is_default']);

        $defaultTaxTypeId = $taxTypes->firstWhere('is_default', true)?->id ?? $taxTypes->first()?->id;

        return view('tenant.quotes.edit', compact(
            'tenant',
            'quote',
            'deals',
            'companies',
            'contacts',
            'salesPeople',
            'products',
            'taxTypes',
            'defaultTaxTypeId'
        ));
    }


    public function update(Request $request, \App\Models\Tenant $tenant, Quote $quote)
    {
        $tenant = app('tenant');
        abort_unless((int) $quote->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'deal_id' => ['nullable','integer'],
            'company_id' => ['nullable','integer'],
            'contact_id' => ['nullable','integer'],

            'issued_at' => ['nullable','date'],
            'valid_until' => ['nullable','date'],
            'notes' => ['nullable','string'],
            'terms' => ['nullable','string'],
            'status' => ['nullable','in:draft,sent,accepted,declined,expired'],

            'sales_person_user_id' => ['required','integer'],

            // default VAT for new rows (header-level)
            'tax_type_id' => ['nullable','integer'],

            'items' => ['required','array','min:1'],
            'items.*.product_id' => ['nullable','integer'],
            'items.*.tax_type_id' => ['nullable','integer'],

            // ✅ sku/unit snapshots (manual fallback)
            'items.*.sku'  => ['nullable','string','max:64'],
            'items.*.unit' => ['nullable','string','max:30'],

            'items.*.name' => ['required','string','max:190'],
            'items.*.description' => ['nullable','string'],
            'items.*.qty' => ['required','numeric','min:0.01'],
            'items.*.unit_price' => ['required','numeric','min:0'],
        ]);

        // Tenant safety (header)
        if (!empty($data['deal_id'])) {
            Deal::where('tenant_id', $tenant->id)->findOrFail((int) $data['deal_id']);
        }
        if (!empty($data['company_id'])) {
            Company::where('tenant_id', $tenant->id)->findOrFail((int) $data['company_id']);
        }
        if (!empty($data['contact_id'])) {
            Contact::where('tenant_id', $tenant->id)->findOrFail((int) $data['contact_id']);
        }
        User::where('tenant_id', $tenant->id)->findOrFail((int) $data['sales_person_user_id']);

        return DB::transaction(function () use ($tenant, $quote, $data) {

            // --------- Batch load Products ----------
            $productIds = collect($data['items'])
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values();

            $productsById = collect();
            if ($productIds->isNotEmpty()) {
                $productsById = Product::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('id', $productIds)
                    ->get(['id','sku','unit','name','description','unit_rate']) // ✅ include sku/unit
                    ->keyBy('id');

                if ($productsById->count() !== $productIds->count()) {
                    abort(404);
                }
            }

            // --------- Batch load Tax Types ----------
            $defaultTaxTypeId = !empty($data['tax_type_id']) ? (int) $data['tax_type_id'] : null;

            $taxTypeIds = collect($data['items'])
                ->pluck('tax_type_id')
                ->filter()
                ->push($defaultTaxTypeId)
                ->filter()
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values();

            $taxTypesById = collect();
            if ($taxTypeIds->isNotEmpty()) {
                $taxTypesById = \App\Models\TaxType::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereIn('id', $taxTypeIds)
                    ->get(['id','name','rate'])
                    ->keyBy('id');

                if ($taxTypesById->count() !== $taxTypeIds->count()) {
                    abort(404);
                }
            }

            // Build snapshot items + totals
            $snapshotItems = [];
            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach (array_values($data['items']) as $pos => $i) {

                $qty = (float) $i['qty'];

                $productId = !empty($i['product_id']) ? (int) $i['product_id'] : null;
                $taxTypeId = !empty($i['tax_type_id']) ? (int) $i['tax_type_id'] : $defaultTaxTypeId;

                // ✅ manual fallback
                $sku  = $i['sku'] ?? null;
                $unit = $i['unit'] ?? null;

                // defaults from post
                $name = $i['name'];
                $desc = $i['description'] ?? null;
                $unitPrice = (float) $i['unit_price'];

                // ✅ snapshot from product if selected
                if ($productId) {
                    $p = $productsById->get($productId);
                    if (!$p) abort(404);

                    $sku  = $p->sku;         // ✅ snapshot sku
                    $unit = $p->unit;        // ✅ snapshot unit
                    $name = $p->name;
                    $desc = $p->description;
                    $unitPrice = (float) $p->unit_rate;
                }

                $lineTotal = round($qty * $unitPrice, 2);
                $subtotal += $lineTotal;

                // snapshot tax
                $taxName = null;
                $taxRate = 0.0;

                if ($taxTypeId) {
                    $t = $taxTypesById->get($taxTypeId);
                    if (!$t) abort(404);

                    $taxName = $t->name;
                    $taxRate = (float) $t->rate;
                }

                $lineTax = round($lineTotal * ($taxRate / 100), 2);
                $taxTotal += $lineTax;

                $snapshotItems[] = [
                    'tenant_id'   => $tenant->id,
                    'product_id'  => $productId,
                    'tax_type_id' => $taxTypeId,

                    'position'    => $pos,

                    // ✅ NEW snapshots
                    'sku'         => $sku,
                    'unit'        => $unit,

                    'name'        => $name,
                    'description' => $desc,
                    'qty'         => $qty,
                    'unit_price'  => $unitPrice,

                    'line_total'  => $lineTotal,

                    // VAT snapshot
                    'tax_name'    => $taxName,
                    'tax_rate'    => $taxRate,
                    'tax_amount'  => $lineTax,
                ];
            }

            $subtotal = round($subtotal, 2);
            $taxTotal = round($taxTotal, 2);
            $total = round($subtotal + $taxTotal, 2);

            $effectiveRate = $subtotal > 0
                ? round(($taxTotal / $subtotal) * 100, 2)
                : 0;

            $quote->update([
                'deal_id' => $data['deal_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'contact_id' => $data['contact_id'] ?? null,

                'sales_person_user_id' => (int) $data['sales_person_user_id'],

                'issued_at' => $data['issued_at'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'status' => $data['status'] ?? $quote->status,

                // header totals (derived from line snapshots)
                'tax_rate' => $effectiveRate,
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total' => $total,

                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            // replace items
            $quote->items()->delete();
            foreach ($snapshotItems as $item) {
                $quote->items()->create($item);
            }

            return redirect()
                ->to(tenant_route('tenant.quotes.show', ['quote' => $quote->id]))
                ->with('success', 'Quote updated.');
        });
    }




   private function nextQuoteNumber(int $tenantId): string
{
    return DB::transaction(function () use ($tenantId) {

        // 1) Ensure the sequence row exists (race-safe because tenant_id is UNIQUE)
        // If two requests try this at the same time, only one insert succeeds.
        try {
            QuoteSequence::firstOrCreate(
                ['tenant_id' => $tenantId],
                ['prefix' => 'Q-', 'next_number' => 1]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Another request created it first (unique constraint hit) -> ignore and continue
        }

        // 2) Lock the existing row for this tenant and generate the next number
        $seq = QuoteSequence::where('tenant_id', $tenantId)
            ->lockForUpdate()
            ->firstOrFail();

        $next = (int) $seq->next_number;
        $prefix = (string) ($seq->prefix ?? 'Q-');

        $number = $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

        // 3) Increment atomically while the row is locked
        $seq->update(['next_number' => $next + 1]);

        return $number;
        });
    }
}
