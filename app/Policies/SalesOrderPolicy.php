<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales_orders.view');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.view')
            && (int) $salesOrder->tenant_id === (int) $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('sales_orders.create');
    }

    public function update(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.update')
            && (int) $salesOrder->tenant_id === (int) $user->tenant_id;
    }

    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.delete')
            && (int) $salesOrder->tenant_id === (int) $user->tenant_id;
    }

    public function pdf(User $user, SalesOrder $salesOrder): bool
    {
        return $user->can('sales_orders.view') && (int)$salesOrder->tenant_id === (int)$user->tenant_id;
    }

    public function export(User $user): bool
    {
        return $user->can('sales_orders.export');
    }
}