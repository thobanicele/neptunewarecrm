<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // -----------------------------
        // 0) Quick guards
        // -----------------------------
        if (!Schema::hasTable('companies') || !Schema::hasTable('products')) {
            return;
        }

        // -----------------------------
        // 1) Repair existing orphaned references
        //    (otherwise adding FKs will fail)
        // -----------------------------
        DB::transaction(function () {

            // 1A) Quotes -> company_id (nullable): set NULL if company missing
            if (Schema::hasTable('quotes') && Schema::hasColumn('quotes', 'company_id')) {
                DB::statement("
                    UPDATE quotes q
                    LEFT JOIN companies c ON c.id = q.company_id
                    SET q.company_id = NULL
                    WHERE q.company_id IS NOT NULL AND c.id IS NULL
                ");
            }

            // 1B) Deals -> company_id (nullable): set NULL if company missing
            if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'company_id')) {
                DB::statement("
                    UPDATE deals d
                    LEFT JOIN companies c ON c.id = d.company_id
                    SET d.company_id = NULL
                    WHERE d.company_id IS NOT NULL AND c.id IS NULL
                ");
            }

            // 1C) Sales orders -> company_id (nullable): set NULL if company missing
            if (Schema::hasTable('sales_orders') && Schema::hasColumn('sales_orders', 'company_id')) {
                DB::statement("
                    UPDATE sales_orders so
                    LEFT JOIN companies c ON c.id = so.company_id
                    SET so.company_id = NULL
                    WHERE so.company_id IS NOT NULL AND c.id IS NULL
                ");
            }

            // 1D) Invoices -> company_id (nullable or not depending on your schema)
            if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'company_id')) {
                // If invoice.company_id is nullable, set NULL when missing
                // If it's NOT nullable, we can't null it, but most schemas allow nullable.
                // We'll attempt a NULL update; if column isn't nullable, the migration will error.
                // If yours is NOT nullable, tell me and I'll switch to a "placeholder company insert" like payments below.
                DB::statement("
                    UPDATE invoices i
                    LEFT JOIN companies c ON c.id = i.company_id
                    SET i.company_id = NULL
                    WHERE i.company_id IS NOT NULL AND c.id IS NULL
                ");
            }

            // 1E) Payments -> company_id (NOT nullable in your migration):
            // Insert placeholder companies for missing company IDs so the FK can be added.
            if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'company_id') && Schema::hasColumn('payments', 'tenant_id')) {
                $missing = DB::table('payments as p')
                    ->leftJoin('companies as c', 'c.id', '=', 'p.company_id')
                    ->whereNull('c.id')
                    ->selectRaw('DISTINCT p.company_id, p.tenant_id')
                    ->get();

                foreach ($missing as $row) {
                    // Avoid duplicates if another table inserted it already
                    $exists = DB::table('companies')->where('id', $row->company_id)->exists();
                    if ($exists) continue;

                    DB::table('companies')->insert([
                        'id'         => (int) $row->company_id,
                        'tenant_id'  => (int) $row->tenant_id,
                        'name'       => '[Deleted Company #' . (int) $row->company_id . ']',
                        'type'       => 'customer',
                        'email'      => null,
                        'phone'      => null,
                        'vat_number' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 1F) Companies -> payment_term_id (nullable): set NULL if payment term missing
            if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'payment_term_id') && Schema::hasTable('payment_terms')) {
                DB::statement("
                    UPDATE companies co
                    LEFT JOIN payment_terms pt ON pt.id = co.payment_term_id
                    SET co.payment_term_id = NULL
                    WHERE co.payment_term_id IS NOT NULL AND pt.id IS NULL
                ");
            }

            // 1G) Quote items -> product_id (nullable): set NULL if product missing
            if (Schema::hasTable('quote_items') && Schema::hasColumn('quote_items', 'product_id')) {
                DB::statement("
                    UPDATE quote_items qi
                    LEFT JOIN products p ON p.id = qi.product_id
                    SET qi.product_id = NULL
                    WHERE qi.product_id IS NOT NULL AND p.id IS NULL
                ");
            }

            // 1H) Sales order items -> product_id (nullable): set NULL if product missing
            if (Schema::hasTable('sales_order_items') && Schema::hasColumn('sales_order_items', 'product_id')) {
                DB::statement("
                    UPDATE sales_order_items soi
                    LEFT JOIN products p ON p.id = soi.product_id
                    SET soi.product_id = NULL
                    WHERE soi.product_id IS NOT NULL AND p.id IS NULL
                ");
            }

            // Invoice items already have product_id FK with nullOnDelete in your schema.
            // We'll replace it with RESTRICT below.
        });

        // -----------------------------
        // 2) Add / replace foreign keys (RESTRICT/NO ACTION)
        // -----------------------------

        // Helper: drop FK without killing the migration if it's missing
        $safeDrop = function (string $table, array $cols) {
            try {
                Schema::table($table, function (Blueprint $t) use ($cols) {
                    $t->dropForeign($cols);
                });
            } catch (\Throwable $e) {
                // ignore
            }
        };

        // ---------- COMPANY RESTRICTS ----------
        if (Schema::hasTable('quotes') && Schema::hasColumn('quotes', 'company_id')) {
            $safeDrop('quotes', ['company_id']);
            Schema::table('quotes', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'company_id')) {
            $safeDrop('deals', ['company_id']);
            Schema::table('deals', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('sales_orders') && Schema::hasColumn('sales_orders', 'company_id')) {
            $safeDrop('sales_orders', ['company_id']);
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'company_id')) {
            // Your existing invoices FK was nullOnDelete; replace with restrict.
            $safeDrop('invoices', ['company_id']);
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'company_id')) {
            // Add restrict for payments -> companies
            $safeDrop('payments', ['company_id']);
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->restrictOnDelete();
            });
        }

        // ---------- PAYMENT TERMS (MASTER) ----------
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'payment_term_id') && Schema::hasTable('payment_terms')) {
            $safeDrop('companies', ['payment_term_id']);
            Schema::table('companies', function (Blueprint $table) {
                $table->foreign('payment_term_id')
                    ->references('id')->on('payment_terms')
                    ->restrictOnDelete();
            });
        }

        // ---------- PRODUCTS (MASTER) ----------
        if (Schema::hasTable('quote_items') && Schema::hasColumn('quote_items', 'product_id')) {
            $safeDrop('quote_items', ['product_id']);
            Schema::table('quote_items', function (Blueprint $table) {
                $table->foreign('product_id')
                    ->references('id')->on('products')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'product_id')) {
            // Replace existing nullOnDelete with restrict
            $safeDrop('invoice_items', ['product_id']);
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->foreign('product_id')
                    ->references('id')->on('products')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('sales_order_items') && Schema::hasColumn('sales_order_items', 'product_id')) {
            $safeDrop('sales_order_items', ['product_id']);
            Schema::table('sales_order_items', function (Blueprint $table) {
                $table->foreign('product_id')
                    ->references('id')->on('products')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Optional: you can drop the foreign keys here, but safe to leave empty in prod migrations.
        // If you want, tell me your DB engine (MySQL/Postgres) and I’ll generate the down() properly.
    }
};
