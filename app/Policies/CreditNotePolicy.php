<?php

namespace App\Policies;

use App\Models\CreditNote;
use App\Models\User;

class CreditNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('credit_notes.view');
    }

    public function view(User $user, CreditNote $creditNote): bool
    {
        return $user->can('credit_notes.view')
            && (int) $creditNote->tenant_id === (int) $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('credit_notes.create');
    }

    public function update(User $user, CreditNote $creditNote): bool
    {
        return $user->can('credit_notes.update')
            && (int) $creditNote->tenant_id === (int) $user->tenant_id;
    }

    public function delete(User $user, CreditNote $creditNote): bool
    {
        return $user->can('credit_notes.delete')
            && (int) $creditNote->tenant_id === (int) $user->tenant_id;
    }

    // Allocation/refund/change state = UPDATE permission
    public function refund(User $user, CreditNote $creditNote): bool
    {
        return $this->update($user, $creditNote);
    }

    // PDF = VIEW permission (until you add a dedicated permission)
    public function pdf(User $user, CreditNote $creditNote): bool
    {
        return $this->view($user, $creditNote);
    }

    // Export = global export gate + viewAny
    public function export(User $user): bool
    {
        return $user->can('export.run') && $user->can('credit_notes.view');
    }
}


