<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BrandPolicy
{
    public function viewAny(User $user): bool { return $user->can('brands.view'); }
    public function view(User $user, Brand $brand): bool {
        return $user->can('brands.view') && (int)$brand->tenant_id === (int)$user->tenant_id;
    }
    public function create(User $user): bool { return $user->can('brands.create'); }
    public function update(User $user, Brand $brand): bool {
        return $user->can('brands.update') && (int)$brand->tenant_id === (int)$user->tenant_id;
    }
    public function delete(User $user, Brand $brand): bool {
        return $user->can('brands.delete') && (int)$brand->tenant_id === (int)$user->tenant_id;
    }
}