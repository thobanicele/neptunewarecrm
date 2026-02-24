<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'slug',
        'is_active',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::saving(function (Category $cat) {
            if (!$cat->tenant_id && tenant()) {
                $cat->tenant_id = tenant()->id;
            }

            if (blank($cat->slug) && filled($cat->name)) {
                $base = Str::slug($cat->name);
                $slug = $base ?: Str::random(8);

                $i = 2;
                while (static::where('tenant_id', $cat->tenant_id)
                    ->where('slug', $slug)
                    ->when($cat->exists, fn($q) => $q->where('id', '!=', $cat->id))
                    ->exists()
                ) {
                    $slug = $base . '-' . $i++;
                }

                $cat->slug = $slug;
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function scopeForTenant($q, $tenantId)
    {
        return $q->where('tenant_id', (int) $tenantId);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
