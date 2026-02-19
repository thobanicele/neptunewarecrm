<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    public function viewAny(User $user): bool { return $user->can('companies.view'); }
    public function view(User $user, Company $company): bool {
        return $user->can('companies.view') && (int)$company->tenant_id === (int)$user->tenant_id;
    }
    public function create(User $user): bool { return $user->can('companies.create'); }
    public function update(User $user, Company $company): bool {
        return $user->can('companies.update') && (int)$company->tenant_id === (int)$user->tenant_id;
    }
    public function delete(User $user, Company $company): bool {
        return $user->can('companies.delete') && (int)$company->tenant_id === (int)$user->tenant_id;
    }
    public function export(User $user): bool { return $user->can('companies.export'); }
    public function statement(User $user): bool { return $user->can('companies.statement'); }
}

