<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function viewAny(User $user): bool { return $user->can('products.view'); }
    public function view(User $user, Product $product): bool {
        return $user->can('products.view') && (int)$product->tenant_id === (int)$user->tenant_id;
    }
    public function create(User $user): bool { return $user->can('products.create'); }
    public function update(User $user, Product $product): bool {
        return $user->can('products.update') && (int)$product->tenant_id === (int)$user->tenant_id;
    }
    public function delete(User $user, Product $product): bool {
        return $user->can('products.delete') && (int)$product->tenant_id === (int)$user->tenant_id;
    }
    public function export(User $user): bool { return $user->can('products.export'); }
}

