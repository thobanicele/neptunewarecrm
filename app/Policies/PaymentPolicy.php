<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    public function viewAny(User $user): bool { return $user->can('payments.view'); }
    public function view(User $user, Payment $payment): bool {
        return $user->can('payments.view') && (int)$payment->tenant_id === (int)$user->tenant_id;
    }
    public function create(User $user): bool { return $user->can('payments.create'); }
    public function update(User $user, Payment $payment): bool {
        return $user->can('payments.update') && (int)$payment->tenant_id === (int)$user->tenant_id;
    }
    public function delete(User $user, Payment $payment): bool {
        return $user->can('payments.delete') && (int)$payment->tenant_id === (int)$user->tenant_id;
    }
    public function export(User $user): bool { return $user->can('payments.export'); }

    public function allocate(User $user, Payment $payment): bool {
        return $user->can('payments.allocate') && $this->update($user, $payment);
    }
    public function allocationsReset(User $user, Payment $payment): bool {
        return $user->can('payments.allocations.reset') && $this->update($user, $payment);
    }
}

