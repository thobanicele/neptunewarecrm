<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthSetPasswordController extends Controller
{
    public function show(Request $request)
    {
        // If user already has a password set, don’t stay here
        if ($request->user()?->password) {
            return redirect()->route('app.home');
        }

        return view('auth.set-password');
    }


    public function store(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        $user->password = Hash::make($request->password);
        $user->save();

        // Clear invite session (optional)
        session()->forget('invite_tenant_subdomain');

        // ✅ Let /app decide where to go next
        return redirect()->route('app.home')->with('success', 'Password saved.');
    }
}

