<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;

class HomeController extends Controller
{
    public function index()
    {
        return view('home.index');
    }

    public function pricing()
    {
        return view('home.pricing');
    }

     public function support()
    {
        return view('home.support');
    }

    public function supportSend(Request $request)
    {
        // 1) Basic validation
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'email' => ['required','email','max:190'],
            'subject' => ['required','string','max:190'],
            'message' => ['required','string','max:5000'],
            'cf-turnstile-response' => ['required','string'],
        ]);

        // 2) Rate limit (prevents double-click spam)
        $key = 'support:' . sha1(strtolower($data['email']) . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Please wait a bit before sending another message.',
            ]);
        }
        RateLimiter::hit($key, 60); // 3 attempts per 60 seconds

        // 3) Turnstile verify
        $token = (string) $request->input('cf-turnstile-response');

        $pending = Http::asForm()->timeout(8);

        // Dev-only quick unblock for Windows/WAMP SSL CA issues
        if (!app()->environment('production')) {
            $pending = $pending->withoutVerifying();
        }

        try {
            $verify = $pending->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => (string) config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Captcha service not reachable. Please try again.',
            ]);
        }

        if (!(bool) data_get($verify->json(), 'success', false)) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Captcha verification failed. Please try again.',
            ]);
        }

        // 4) Resolve support inbox safely (never null)
        $to = config('mail.support_to')
            ?: env('MAIL_TO_SUPPORT')
            ?: config('mail.from.address')
            ?: 'support@neptuneware.com';

        // 5) Send to support inbox (with Reply-To)
        try {
            Mail::raw(
                "From: {$data['name']} <{$data['email']}>\nSubject: {$data['subject']}\n\n{$data['message']}",
                function ($m) use ($to, $data) {
                    $m->to($to)
                    ->subject('[Support] ' . $data['subject'])
                    ->replyTo($data['email'], $data['name']);
                }
            );
        } catch (\Throwable $e) {
            // If support mail fails, don't pretend it worked
            throw ValidationException::withMessages([
                'email' => 'We could not send your message right now. Please try again in a moment.',
            ]);
        }

        // 6) Send confirmation to user (only after support mail succeeded)
        try {
            Mail::raw(
                "Hi {$data['name']},\n\nWe received your support request:\nSubject: {$data['subject']}\n\nWe’ll respond shortly.\n\nNeptuneWare CRM",
                fn ($m) => $m->to($data['email'])->subject('We received your support request')
            );
        } catch (\Throwable $e) {
            // Don't block success if confirmation fails
            \Log::warning('Support confirmation email failed: '.$e->getMessage(), [
                'email' => $data['email'],
            ]);
        }

        // 7) Redirect to thank-you page (prevents “did it send?” doubt)
        return redirect()->route('support.thanks');
    }

    public function supportThanks()
    {
        return view('home.support-thanks');
    }

    public function help()
    {
        return view('home.help-center');
    }

    public function privacy()
    {
        return view('home.privacy');
    }

    public function terms()
    {
        return view('home.terms');
    }
}
