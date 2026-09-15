<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * FR-A2 (email or reg_no login) + FR-A4 (role-based landing page).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();          // also blocks suspended accounts

        $request->session()->regenerate();

        // Each role lands on its own portal — see User::homeRoute()
        return redirect()->intended(route($request->user()->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
