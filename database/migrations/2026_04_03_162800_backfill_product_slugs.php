<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Product::query()
            ->select('id', 'tenant_id', 'name', 'slug')
            ->whereNull('slug')
            ->orWhere('slug', '')
            ->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    $base = Str::slug($product->name);
                    $slug = $base ?: 'product-' . $product->id;
                    $i = 2;

                    while (
                        Product::query()
                            ->where('tenant_id', $product->tenant_id)
                            ->where('slug', $slug)
                            ->where('id', '!=', $product->id)
                            ->exists()
                    ) {
                        $slug = ($base ?: 'product-' . $product->id) . '-' . $i++;
                    }

                    $product->slug = $slug;
                    $product->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        //
    }
};