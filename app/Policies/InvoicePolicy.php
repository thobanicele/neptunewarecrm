<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool { return $user->can('invoices.view'); }

    public function view(User $user, Invoice $invoice): bool {
        return $user->can('invoices.view') && (int)$invoice->tenant_id === (int)$user->tenant_id;
    }

    public function create(User $user): bool { return $user->can('invoices.create'); }

    public function update(User $user, Invoice $invoice): bool {
        return $user->can('invoices.update') && (int)$invoice->tenant_id === (int)$user->tenant_id;
    }

    public function delete(User $user, Invoice $invoice): bool {
        return $user->can('invoices.delete') && (int)$invoice->tenant_id === (int)$user->tenant_id;
    }

    public function pdf(User $user, Invoice $invoice): bool {
        return $user->can('invoices.pdf') && $this->view($user, $invoice);
    }

    // ✅ Issue uses update permission (issuing = state change)
    public function issue(User $user, Invoice $invoice): bool {
        return $user->can('invoices.update') && $this->update($user, $invoice);
    }

    // ✅ Mark paid = finance action; you can treat it as update too
    public function markPaid(User $user, Invoice $invoice): bool {
        return $user->can('invoices.update') && $this->update($user, $invoice);
    }

    // ✅ Sending email uses invoices.send
    public function sendEmail(User $user, Invoice $invoice): bool {
        return $user->can('invoices.send') && $this->view($user, $invoice);
    }

    // ✅ Export is global permission + invoices.view
    public function export(User $user): bool {
        return $user->can('export.run') && $user->can('invoices.view');
    }

    // ✅ Statement is also export/run (or invoices.view) depending on your plan
    public function statement(User $user): bool {
        return $user->can('invoices.view'); // or export.run if you want it pro-only
    }
}
