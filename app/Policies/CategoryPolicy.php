<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    public function viewAny(User $user): bool { return $user->can('categories.view'); }
    public function view(User $user, Category $category): bool {
        return $user->can('categories.view') && (int)$category->tenant_id === (int)$user->tenant_id;
    }
    public function create(User $user): bool { return $user->can('categories.create'); }
    public function update(User $user, Category $category): bool {
        return $user->can('categories.update') && (int)$category->tenant_id === (int)$user->tenant_id;
    }
    public function delete(User $user, Category $category): bool {
        return $user->can('categories.delete') && (int)$category->tenant_id === (int)$user->tenant_id;
    }
}

