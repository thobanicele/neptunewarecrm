<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quotes.view');
    }

    public function view(User $user, Quote $quote): bool
    {
        return $user->can('quotes.view')
            && (int) $quote->tenant_id === (int) $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('quotes.create');
    }

    public function update(User $user, Quote $quote): bool
    {
        return $user->can('quotes.update')
            && (int) $quote->tenant_id === (int) $user->tenant_id;
    }

    public function delete(User $user, Quote $quote): bool
    {
        return $user->can('quotes.delete')
            && (int) $quote->tenant_id === (int) $user->tenant_id;
    }

    public function pdf(User $user, Quote $quote): bool
    {
        return $user->can('quotes.pdf') && $this->view($user, $quote);
    }

    public function markSent(User $user, Quote $quote): bool
    {
        return $user->can('quotes.send') && $this->update($user, $quote);
    }

    public function accept(User $user, Quote $quote): bool
    {
        return $user->can('quotes.accept') && $this->update($user, $quote);
    }

    public function decline(User $user, Quote $quote): bool
    {
        return $user->can('quotes.decline') && $this->update($user, $quote);
    }

    // Not in config: treat convert as UPDATE for now (until you add quotes.convert)
    public function convertToInvoice(User $user, Quote $quote): bool
    {
        return $this->update($user, $quote);
    }

    // Export is global: export.run + must be able to view quotes
    public function export(User $user): bool
    {
        return $user->can('export.run') && $user->can('quotes.view');
    }
}


