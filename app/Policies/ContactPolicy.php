<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContactPolicy
{
    public function viewAny(User $user): bool { return $user->can('contacts.view'); }
    public function view(User $user, Contact $contact): bool {
        return $user->can('contacts.view') && (int)$contact->tenant_id === (int)$user->tenant_id;
    }
    public function create(User $user): bool { return $user->can('contacts.create'); }
    public function update(User $user, Contact $contact): bool {
        return $user->can('contacts.update') && (int)$contact->tenant_id === (int)$user->tenant_id;
    }
    public function delete(User $user, Contact $contact): bool {
        return $user->can('contacts.delete') && (int)$contact->tenant_id === (int)$user->tenant_id;
    }
    public function export(User $user): bool { return $user->can('contacts.export'); }

    // Leads-specific abilities (LeadController calls these)
    public function leadsViewAny(User $user): bool { return $user->can('leads.view'); }
    public function leadsCreate(User $user): bool { return $user->can('leads.create'); }
    public function leadsUpdate(User $user, Contact $contact): bool {
        return $user->can('leads.update') && (int)$contact->tenant_id === (int)$user->tenant_id;
    }
    public function leadsDelete(User $user, Contact $contact): bool {
        return $user->can('leads.delete') && (int)$contact->tenant_id === (int)$user->tenant_id;
    }
    public function leadsExport(User $user): bool { return $user->can('leads.export'); }
    public function leadsStage(User $user, Contact $contact): bool { return $user->can('leads.stage') && $this->leadsUpdate($user,$contact); }
    public function leadsQualify(User $user, Contact $contact): bool { return $user->can('leads.qualify') && $this->leadsUpdate($user,$contact); }
}



