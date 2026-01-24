<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    // ✅ Super admin can do anything
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    // ✅ Needed for index() (authorizeResource uses viewAny)
    public function viewAny(User $user): bool
    {
        // any onboarded user can view their tenant deals
        return !is_null($user->tenant_id);
    }

    public function view(User $user, Deal $deal): bool
    {
        if ($user->hasRole('super_admin')) return true;

        // same tenant
        if ((int) $user->tenant_id !== (int) $deal->tenant_id) return false;

        // allow tenant roles (adjust to your roles)
        return $user->hasAnyRole(['tenant_owner','tenant_admin','tenant_staff']);
    }

    public function create(User $user): bool
    {
        return !is_null($user->tenant_id);
    }

    public function update(User $user, Deal $deal): bool
    {
        return (int) $user->tenant_id === (int) $deal->tenant_id;
    }

    public function delete(User $user, Deal $deal): bool
    {
        return (int) $user->tenant_id === (int) $deal->tenant_id;
    }
}

