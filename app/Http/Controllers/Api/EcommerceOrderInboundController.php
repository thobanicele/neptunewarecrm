<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcommerceOrderInboundController extends Controller
{
    /**
     * Idempotent upsert:
     * - Order unique: (tenant_id, external_order_id)
     * - Items:
     *   - If external_item_id present: upsert by (tenant_id, order_id, external_item_id)
     *   - Else: replace all items
     */
    public function upsert(Request $request)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'external_order_id' => ['required', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:40'],
            'currency' => ['nullable', 'string', 'max:10'],

            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],

            'customer' => ['nullable', 'array'],
            'customer.name' => ['nullable', 'string', 'max:255'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:50'],

            'billing_address' => ['nullable', 'array'],
            'shipping_address' => ['nullable', 'array'],

            'placed_at' => ['nullable', 'date'],
            'external_updated_at' => ['nullable', 'date'],

            'meta' => ['nullable', 'array'],

            'items' => ['nullable', 'array'],
            'items.*.external_item_id' => ['nullable', 'string', 'max:100'],
            'items.*.position' => ['nullable', 'integer', 'min:0'],
            'items.*.sku' => ['nullable', 'string', 'max:80'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.meta' => ['nullable', 'array'],

            'payment_status' => ['nullable', 'string', 'max:30'],
            'fulfillment_status' => ['nullable', 'string', 'max:30'],
            'paid_at' => ['nullable', 'date'],
            'fulfilled_at' => ['nullable', 'date'],
        ]);

        $externalOrderId = $data['external_order_id'];
        $items = $data['items'] ?? [];
        $rawPayload = $request->all();

        $result = DB::transaction(function () use ($tenant, $externalOrderId, $data, $items, $rawPayload) {
            $order = EcommerceOrder::where('tenant_id', $tenant->id)
                ->where('external_order_id', $externalOrderId)
                ->first();

            $oldPayment = $order?->payment_status;
            $oldFulfillment = $order?->fulfillment_status;
            $oldStatus = $order?->status;

            $creating = !$order;

            $payload = [
                'tenant_id' => $tenant->id,
                'external_order_id' => $externalOrderId,
                'source' => $data['source'] ?? null,
                'status' => $data['status'] ?? ($order?->status ?? 'pending'),
                'currency' => $data['currency'] ?? null,

                'subtotal' => $data['subtotal'] ?? 0,
                'tax_total' => $data['tax_total'] ?? 0,
                'shipping_total' => $data['shipping_total'] ?? 0,
                'discount_total' => $data['discount_total'] ?? 0,
                'grand_total' => $data['grand_total'] ?? 0,

                'customer_name' => data_get($data, 'customer.name'),
                'customer_email' => data_get($data, 'customer.email'),
                'customer_phone' => data_get($data, 'customer.phone'),

                'billing_address' => $data['billing_address'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,

                'placed_at' => $data['placed_at'] ?? null,
                'external_updated_at' => $data['external_updated_at'] ?? null,

                'meta' => $data['meta'] ?? null,
                'raw_payload' => $rawPayload,

                'payment_status' => $data['payment_status'] ?? ($order?->payment_status ?? 'pending'),
                'fulfillment_status' => $data['fulfillment_status'] ?? ($order?->fulfillment_status ?? 'unfulfilled'),
                'paid_at' => $data['paid_at'] ?? ($order?->paid_at ?? null),
                'fulfilled_at' => $data['fulfilled_at'] ?? ($order?->fulfilled_at ?? null),
            ];

            if ($creating) {
                $order = EcommerceOrder::create($payload);
            } else {
                $order->fill($payload);
                $order->save();
            }

            // ---- status-change activity logs (from -> to) ----
            if (class_exists(\App\Services\ActivityLogger::class) && !$creating) {

                if (!is_null($oldPayment) && $oldPayment !== $order->payment_status) {
                    app(\App\Services\ActivityLogger::class)->log(
                        $tenant->id,
                        'ecommerce_order.payment_status_changed',
                        $order,
                        [
                            'from' => $oldPayment,
                            'to' => $order->payment_status,
                            'external_order_id' => $order->external_order_id,
                        ],
                        null
                    );
                }

                if (!is_null($oldFulfillment) && $oldFulfillment !== $order->fulfillment_status) {
                    app(\App\Services\ActivityLogger::class)->log(
                        $tenant->id,
                        'ecommerce_order.fulfillment_status_changed',
                        $order,
                        [
                            'from' => $oldFulfillment,
                            'to' => $order->fulfillment_status,
                            'external_order_id' => $order->external_order_id,
                        ],
                        null
                    );
                }

                if (!is_null($oldStatus) && $oldStatus !== $order->status) {
                    app(\App\Services\ActivityLogger::class)->log(
                        $tenant->id,
                        'ecommerce_order.status_changed',
                        $order,
                        [
                            'from' => $oldStatus,
                            'to' => $order->status,
                            'external_order_id' => $order->external_order_id,
                        ],
                        null
                    );
                }
            }

            // Items strategy:
            $hasExternalItemIds = collect($items)->contains(fn ($i) => !empty($i['external_item_id'] ?? null));

            if (!$hasExternalItemIds) {
                $order->items()->delete();

                foreach ($items as $idx => $it) {
                    EcommerceOrderItem::create([
                        'tenant_id' => $tenant->id,
                        'ecommerce_order_id' => $order->id,
                        'external_item_id' => null,
                        'position' => (int) ($it['position'] ?? $idx),
                        'sku' => $it['sku'] ?? null,
                        'name' => $it['name'] ?? null,
                        'qty' => $it['qty'] ?? 1,
                        'unit_price' => $it['unit_price'] ?? 0,
                        'tax_total' => $it['tax_total'] ?? 0,
                        'discount_total' => $it['discount_total'] ?? 0,
                        'line_total' => $it['line_total'] ?? 0,
                        'meta' => $it['meta'] ?? null,
                    ]);
                }
            } else {
                foreach ($items as $idx => $it) {
                    $externalItemId = $it['external_item_id'] ?? null;
                    if (!$externalItemId) continue;

                    EcommerceOrderItem::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'ecommerce_order_id' => $order->id,
                            'external_item_id' => $externalItemId,
                        ],
                        [
                            'position' => (int) ($it['position'] ?? $idx),
                            'sku' => $it['sku'] ?? null,
                            'name' => $it['name'] ?? null,
                            'qty' => $it['qty'] ?? 1,
                            'unit_price' => $it['unit_price'] ?? 0,
                            'tax_total' => $it['tax_total'] ?? 0,
                            'discount_total' => $it['discount_total'] ?? 0,
                            'line_total' => $it['line_total'] ?? 0,
                            'meta' => $it['meta'] ?? null,
                        ]
                    );
                }
            }

            // ✅ Less noisy created/updated logs
            if (class_exists(\App\Services\ActivityLogger::class)) {
                if ($creating) {
                    app(\App\Services\ActivityLogger::class)->log(
                        $tenant->id,
                        'ecommerce_order.created',
                        $order,
                        [
                            'external_order_id' => $order->external_order_id,
                            'status' => $order->status,
                            'source' => $order->source,
                            'grand_total' => (string) $order->grand_total,
                        ],
                        null
                    );
                } else {
                    $dirty = $order->getChanges();
                    $statusKeys = [
                        'status', 'payment_status', 'fulfillment_status',
                        'paid_at', 'fulfilled_at', 'external_updated_at',
                        'raw_payload', 'updated_at',
                    ];
                    $nonStatusChanges = array_values(array_diff(array_keys($dirty), $statusKeys));

                    if (!empty($nonStatusChanges)) {
                        app(\App\Services\ActivityLogger::class)->log(
                            $tenant->id,
                            'ecommerce_order.updated',
                            $order,
                            [
                                'external_order_id' => $order->external_order_id,
                                'changed' => $nonStatusChanges,
                            ],
                            null
                        );
                    }
                }
            }

            return [$order, $creating];
        });

        /** @var EcommerceOrder $order */
        [$order, $creating] = $result;

        return response()->json([
            'ok' => true,
            'created' => (bool) $creating,
            'ecommerce_order' => [
                'id' => $order->id,
                'tenant_id' => $order->tenant_id,
                'external_order_id' => $order->external_order_id,
                'status' => $order->status,
                'grand_total' => $order->grand_total,
                'currency' => $order->currency,
                'updated_at' => $order->updated_at,
            ],
        ], $creating ? 201 : 200);
    }
}