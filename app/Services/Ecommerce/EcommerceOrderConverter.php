<?php

namespace App\Services\Ecommerce;

use App\Models\EcommerceOrder;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;

class EcommerceOrderConverter
{
    public function __construct(
        private readonly DocumentNumberService $numbers
    ) {}

    private function snapshot(?array $addr): ?string
    {
        if (!$addr) return null;

        return json_encode($addr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function convertToSalesOrder(EcommerceOrder $order, ?int $userId = null): SalesOrder
    {
        $tenant = app('tenant');

        if ($order->converted_sales_order_id) {
            return SalesOrder::where('tenant_id', $tenant->id)
                ->where('id', $order->converted_sales_order_id)
                ->firstOrFail();
        }

        return DB::transaction(function () use ($tenant, $order, $userId) {

            $existing = SalesOrder::where('tenant_id', $tenant->id)
                ->where('source', 'ecommerce')
                ->where('source_id', $order->id)
                ->first();

            if ($existing) {
                $order->update([
                    'converted_sales_order_id' => $existing->id,
                    'converted_at' => now(),
                ]);

                return $existing;
            }

            $so = new SalesOrder();
            $so->tenant_id = $tenant->id;
            $so->source = 'ecommerce';
            $so->source_id = $order->id;
            $so->external_order_id = $order->external_order_id;

            $so->sales_order_number = $this->numbers->nextSalesOrderNumber($tenant->id);

            $so->billing_address_snapshot = $this->snapshot($order->billing_address);
            $so->shipping_address_snapshot = $this->snapshot($order->shipping_address);

            // ✅ Customer linking propagation (from ecommerce order -> sales order)
            $so->company_id = $order->company_id ?? null;
            $so->contact_id = $order->contact_id ?? null;

            $so->status = 'draft';

            $so->currency = $order->currency ?: 'ZAR';
            $so->subtotal = $order->subtotal ?? 0;
            $so->discount_amount = $order->discount_total ?? 0;
            $so->tax_amount = $order->tax_total ?? 0;

            $subtotal = (float) $so->subtotal;
            $taxAmount = (float) $so->tax_amount;
            $so->tax_rate = $subtotal > 0 ? round(($taxAmount / $subtotal) * 100, 2) : 0;

            $so->total = $order->grand_total ?? (($so->subtotal - $so->discount_amount) + $so->tax_amount);

            $so->save();

            // ✅ SKU → Product match lookup
            $skus = $order->items()
                ->pluck('sku')
                ->filter()
                ->map(fn ($s) => strtoupper(trim((string) $s)))
                ->unique()
                ->values();

            $productBySku = Product::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('sku', $skus)
                ->get(['id', 'sku', 'unit', 'tax_type_id', 'name'])
                ->keyBy(fn ($p) => strtoupper(trim($p->sku)));

            // ✅ Unmatched SKU tracking
            $unmatchedSkus = [];

            // ✅ copy items with product links
            foreach ($order->items()->orderBy('position')->get() as $it) {
                $skuKey = $it->sku ? strtoupper(trim((string) $it->sku)) : null;
                $prod = $skuKey ? ($productBySku[$skuKey] ?? null) : null;

                if ($skuKey && !$prod) {
                    $unmatchedSkus[] = $skuKey;
                }

                $so->items()->create([
                    'tenant_id' => $tenant->id,
                    'product_id' => $prod?->id,
                    'sku' => $it->sku,
                    'unit' => $prod?->unit,
                    'tax_type_id' => $prod?->tax_type_id,
                    'position' => $it->position ?? 0,
                    'name' => $it->name ?: ($prod?->name ?? 'Item'),
                    'description' => null,
                    'qty' => $it->qty ?? 1,
                    'unit_price' => $it->unit_price ?? 0,
                    'discount_pct' => 0,
                    'discount_amount' => $it->discount_total ?? 0,
                    'tax_name' => null,
                    'tax_rate' => 0,
                    'tax_amount' => $it->tax_total ?? 0,
                    'line_total' => $it->line_total ?? 0,
                ]);
            }

            $order->update([
                'converted_sales_order_id' => $so->id,
                'converted_at' => now(),
            ]);

            // ✅ Activity logs (positional args)
            if (class_exists(\App\Services\ActivityLogger::class)) {

                app(\App\Services\ActivityLogger::class)->log(
                    $tenant->id,
                    'ecommerce_order.converted_to_sales_order',
                    $order,
                    [
                        'sales_order_id' => $so->id,
                        'sales_order_number' => $so->sales_order_number,
                    ],
                    $userId
                );

                app(\App\Services\ActivityLogger::class)->log(
                    $tenant->id,
                    'sales_order.created_from_ecommerce_order',
                    $so,
                    [
                        'ecommerce_order_id' => $order->id,
                        'external_order_id' => $order->external_order_id,
                    ],
                    $userId
                );

                // ✅ Unmatched SKU logs (both sides)
                $unmatchedSkus = array_values(array_unique($unmatchedSkus));

                if (!empty($unmatchedSkus)) {
                    app(\App\Services\ActivityLogger::class)->log(
                        $tenant->id,
                        'sales_order.unmatched_skus',
                        $so,
                        [
                            'unmatched_skus' => $unmatchedSkus,
                            'ecommerce_order_id' => $order->id,
                            'external_order_id' => $order->external_order_id,
                        ],
                        $userId
                    );

                    app(\App\Services\ActivityLogger::class)->log(
                        $tenant->id,
                        'ecommerce_order.unmatched_skus',
                        $order,
                        [
                            'unmatched_skus' => $unmatchedSkus,
                            'sales_order_id' => $so->id,
                            'sales_order_number' => $so->sales_order_number,
                        ],
                        $userId
                    );
                }
            }

            return $so;
        });
    }

    public function createInvoiceFromSalesOrder(SalesOrder $so, ?int $userId = null): Invoice
    {
        $tenant = app('tenant');

        // ✅ Idempotent check BEFORE number generation
        $existing = Invoice::where('tenant_id', $tenant->id)
            ->where('source', 'sales_order')
            ->where('source_id', $so->id)
            ->first();

        if ($existing) return $existing;

        return DB::transaction(function () use ($tenant, $so, $userId) {

            // Re-check in transaction (extra safety)
            $existing2 = Invoice::where('tenant_id', $tenant->id)
                ->where('source', 'sales_order')
                ->where('source_id', $so->id)
                ->lockForUpdate()
                ->first();

            if ($existing2) return $existing2;

            $inv = new Invoice();
            $inv->tenant_id = $tenant->id;

            // mapping + link
            $inv->source = 'sales_order';
            $inv->source_id = $so->id;

            $inv->sales_order_id = $so->id;
            $inv->sales_order_number = $so->sales_order_number;

            // ✅ required invoice_number
            $inv->invoice_number = $this->numbers->nextInvoiceNumber($tenant->id);

            // statuses
            $inv->status = 'draft';
            $inv->payment_status = 'unpaid';

            // dates
            $inv->issued_at = now()->toDateString();
            $inv->due_at = null;

            // totals (schema)
            $inv->currency = $so->currency ?? 'ZAR';
            $inv->subtotal = $so->subtotal ?? 0;
            $inv->discount_amount = $so->discount_amount ?? 0;
            $inv->tax_rate = $so->tax_rate ?? 0;
            $inv->tax_amount = $so->tax_amount ?? 0;
            $inv->total = $so->total ?? 0;

            // snapshots
            $inv->billing_address_snapshot = $so->billing_address_snapshot;
            $inv->shipping_address_snapshot = $so->shipping_address_snapshot;

            // optional links
            $inv->quote_id = $so->quote_id;
            $inv->quote_number = $so->quote_number;
            $inv->deal_id = $so->deal_id;

            // ✅ customer propagation already comes via sales order
            $inv->company_id = $so->company_id;
            $inv->contact_id = $so->contact_id;

            $inv->save();

            // copy items
            foreach ($so->items()->orderBy('position')->get() as $it) {
                $inv->items()->create([
                    'tenant_id' => $tenant->id,
                    'product_id' => $it->product_id,
                    'sku' => $it->sku,
                    'unit' => $it->unit,
                    'tax_type_id' => $it->tax_type_id,
                    'position' => $it->position ?? 0,
                    'name' => $it->name,
                    'description' => $it->description,
                    'qty' => $it->qty ?? 1,
                    'unit_price' => $it->unit_price ?? 0,
                    'discount_pct' => $it->discount_pct ?? 0,
                    'discount_amount' => $it->discount_amount ?? 0,
                    'tax_name' => $it->tax_name,
                    'tax_rate' => $it->tax_rate ?? 0,
                    'line_total' => $it->line_total ?? 0,
                    'tax_amount' => $it->tax_amount ?? 0,
                ]);
            }

            if (class_exists(\App\Services\ActivityLogger::class)) {
                app(\App\Services\ActivityLogger::class)->log(
                    $tenant->id,
                    'invoice.created_from_sales_order',
                    $inv,
                    ['sales_order_id' => $so->id],
                    $userId
                );
            }

            return $inv;
        });
    }
}