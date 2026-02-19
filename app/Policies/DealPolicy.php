<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('deals.view');
    }

    public function view(User $user, Deal $deal): bool
    {
        return $user->can('deals.view') && (int)$deal->tenant_id === (int)$user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('deals.create');
    }

    public function update(User $user, Deal $deal): bool
    {
        return $user->can('deals.update') && (int)$deal->tenant_id === (int)$user->tenant_id;
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $user->can('deals.delete') && (int)$deal->tenant_id === (int)$user->tenant_id;
    }

    public function export(User $user): bool
    {
        return $user->can('deals.export');
    }

    public function updateStage(User $user, Deal $deal): bool
    {
        return $this->update($user, $deal);
    }
}


