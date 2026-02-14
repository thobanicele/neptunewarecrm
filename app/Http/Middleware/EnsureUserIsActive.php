<?php
namespace App\Http\Middleware;

use Closure;

class EnsureUserIsActive
{
    public function handle($request, Closure $next)
    {
        $u = $request->user();
        if ($u && data_get($u, 'is_active') === false) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account is inactive. Contact your workspace admin.');
        }
        return $next($request);
    }
}