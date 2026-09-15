<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-F1 — a user suspended DURING an active session must lose access on their
 * next request, not merely be blocked at the next login. LoginRequest handles
 * login-time; this handles mid-session suspension.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => 'Your account has been suspended. Please contact the Lost & Found Office.',
            ]);
        }

        return $next($request);
    }
}
