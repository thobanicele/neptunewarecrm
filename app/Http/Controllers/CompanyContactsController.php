<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;

class CompanyContactsController extends Controller
{
    public function index(Request $request, string $tenantKey, Company $company)
    {
        $tenant = app('tenant');
        abort_unless((int) $company->tenant_id === (int) $tenant->id, 404);

        // Optional: allow picking a contact and ensuring it's included
        $selectedId = $request->integer('selected_id');

        $contacts = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json([
            'company_id' => $company->id,
            'selected_id' => $selectedId,
            'contacts' => $contacts,
        ]);
    }
}

