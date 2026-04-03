<?php

namespace App\Models;

use App\Models\{QuoteItem, InvoiceItem, SalesOrderItem, CreditNoteItem};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'tenant_id',
        'brand_id',
        'category_id',
        'sku',
        'name',
        'slug',
        'description',
        'image_path',
        'unit_rate',
        'unit',
        'is_active',
        'is_featured',
        'is_storefront_visible',
        'currency',
        'tax_type_id',
    ];

    protected $appends = [
        'image_url',
    ];

    protected $casts = [
        'unit_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_storefront_visible' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (! $product->tenant_id && tenant()) {
                $product->tenant_id = tenant()->id;
            }

            if (blank($product->slug) && filled($product->name)) {
                $base = Str::slug($product->name);
                $slug = $base ?: Str::random(8);
                $i = 2;

                while (
                    static::query()
                        ->where('tenant_id', $product->tenant_id)
                        ->where('slug', $slug)
                        ->when($product->exists, fn ($q) => $q->where('id', '!=', $product->id))
                        ->exists()
                ) {
                    $slug = $base . '-' . $i++;
                }

                $product->slug = $slug;
            }
        });
    }

    public function taxType()
    {
        return $this->belongsTo(\App\Models\TaxType::class, 'tax_type_id');
    }

    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class, 'brand_id');
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class, 'category_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function isUsedInTransactions(): bool
    {
        $tenantId = (int) $this->tenant_id;

        return QuoteItem::where('tenant_id', $tenantId)->where('product_id', $this->id)->exists()
            || InvoiceItem::where('tenant_id', $tenantId)->where('product_id', $this->id)->exists()
            || SalesOrderItem::where('tenant_id', $tenantId)->where('product_id', $this->id)->exists()
            || CreditNoteItem::where('tenant_id', $tenantId)->where('product_id', $this->id)->exists();
    }
}




