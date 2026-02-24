<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'cf-turnstile-response' => ['required', 'string'],
        ]);

        $token = (string) $request->input('cf-turnstile-response');

        // ✅ Turnstile verify (dev-friendly SSL handling)
        $pending = Http::asForm()->timeout(8);

        // Option B: dev-only quick unblock for Windows/WAMP SSL CA issues
        if (!app()->environment('production')) {
            $pending = $pending->withoutVerifying();
        }

        try {
            $verify = $pending->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Captcha service not reachable. Please try again.',
            ]);
        }

        $ok = (bool) data_get($verify->json(), 'success', false);

        if (!$ok) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Captcha verification failed. Please try again.',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));
        Auth::login($user);

        if (session()->has('invite_token')) {
            $token = session('invite_token');

            // clear session first (avoid loops)
            session()->forget(['invite_token', 'invite_email']);

            return redirect()->route('tenant.invites.accept', ['token' => $token]);
        }

        return redirect()->route('verification.notice');
    }
}
