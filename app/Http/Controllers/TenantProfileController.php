<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class TenantProfileController extends Controller
{
    public function edit(Request $request, $tenant)
    {
        return view('tenant.profile.edit', [
            'tenant' => app('tenant'),
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request, $tenant)
    {
        $user = $request->user();
        $tenantModel = app('tenant');

        // hard tenant safety
        abort_unless((int) $user->tenant_id === (int) $tenantModel->id, 404);

        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'email' => ['required','email','max:190','unique:users,email,' . $user->id],

            'avatar' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],

            // optional password change
            'current_password' => ['nullable','required_with:password'],
            'password' => ['nullable','confirmed', Password::defaults()],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        // Password change (optional)
        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
            }
            $user->password = Hash::make($request->password);
        }

        // Avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $path = $request->file('avatar')->store(
                'avatars/tenant_' . $tenantModel->id,
                'public'
            );

            $user->avatar_path = $path;
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }
}
