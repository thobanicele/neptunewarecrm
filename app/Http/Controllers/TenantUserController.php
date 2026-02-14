<?php
namespace App\Http\Controllers;

use App\Mail\TenantInviteMail;
use App\Models\TenantInvite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TenantUserController extends Controller
{
    public function index(string $tenant)
    {
        $tenant = app('tenant');

        $users = User::query()
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $invites = TenantInvite::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('accepted_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'invites_page')
            ->withQueryString();

        $roles = config('plans.users.invites.allowed_roles', ['tenant_admin','tenant_staff']);

        return view('tenant.settings.users.index', compact('tenant', 'users', 'invites', 'roles'));
    }

    public function invite(Request $request, string $tenant)
    {
        $tenant = app('tenant');

        $allowed = config('plans.users.invites.allowed_roles', ['tenant_admin','tenant_staff']);

        $data = $request->validate([
            'email' => ['required','email','max:255'],
            'role'  => ['required','string', 'in:' . implode(',', $allowed)],
            'resend'=> ['nullable','in:1'],
        ]);

        // If user already exists + already belongs to this tenant
        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser && (int)$existingUser->tenant_id === (int)$tenant->id) {
            return back()->with('error', 'This user is already a member of your workspace.');
        }

        // If user already belongs to another tenant (your policy decision)
        if ($existingUser && $existingUser->tenant_id && (int)$existingUser->tenant_id !== (int)$tenant->id) {
            return back()->with('error', 'This user already belongs to another workspace.');
        }

        // create token (store hash)
        $rawToken = Str::random(48);
        $tokenHash = hash('sha256', $rawToken);

        $expiresDays = (int) config('plans.users.invites.expires_days', 7);

        // Create-or-update invite row (resend friendly)
        $invite = TenantInvite::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => $data['email']],
            [
                'role' => $data['role'],
                'token_hash' => $tokenHash,
                'expires_at' => now()->addDays($expiresDays),
                'accepted_at' => null,
                'invited_by' => auth()->id(),
            ]
        );

        // Accept URL (outside tenant area so user can accept before joining)
        $acceptUrl = route('tenant.invites.accept', ['token' => $rawToken]);

        Mail::to($invite->email)->send(new TenantInviteMail($invite->fresh(['tenant']), $acceptUrl));

        return back()->with('success', 'Invite sent to ' . $invite->email);
    }

    /**
     * Accept invite (guest or logged-in).
     * Route is public; it will force login if needed.
     */
    public function accept(string $token)
    {
        $tokenHash = hash('sha256', $token);

        $invite = TenantInvite::query()
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$invite) {
            return redirect()->route('home')->with('error', 'Invalid invite link.');
        }

        if ($invite->isAccepted()) {
            return redirect()->route('login')->with('success', 'Invite already accepted. Please log in.');
        }

        if ($invite->isExpired()) {
            return redirect()->route('login')->with('error', 'Invite expired. Ask your admin to resend it.');
        }

        // If not logged in, push the token into session and redirect to login/register.
        if (!auth()->check()) {
            session([
                'invite_token' => $token,
                'invite_email' => $invite->email,
            ]);

            // If you want to prefer register when user doesn't exist:
            $exists = User::where('email', $invite->email)->exists();
            return $exists
                ? redirect()->route('login')->with('success', 'Please log in to accept the invite.')
                : redirect()->route('register')->with('success', 'Create your account to accept the invite.');
        }

        $user = auth()->user();

        // Hard safety: email must match (stops someone accepting on wrong account)
        if (strtolower($user->email) !== strtolower($invite->email)) {
            return redirect()->route('home')->with('error', 'You are logged in as a different email. Please log in using ' . $invite->email . '.');
        }

        // If user already in another tenant, block (or handle multi-tenant memberships later)
        if ($user->tenant_id && (int)$user->tenant_id !== (int)$invite->tenant_id) {
            return redirect()->route('home')->with('error', 'Your account already belongs to another workspace.');
        }

        // Attach user to tenant
        $user->forceFill(['tenant_id' => $invite->tenant_id])->save();

        // Assign role (Spatie)
        $user->syncRoles([$invite->role]);

        // mark accepted
        $invite->forceFill(['accepted_at' => now()])->save();

        // redirect into tenant dashboard
        $tenant = $invite->tenant; // relationship
        return redirect()->route('tenant.dashboard', ['tenant' => $tenant->subdomain])
            ->with('success', 'Welcome! You’ve joined ' . $tenant->name . '.');
    }
}



