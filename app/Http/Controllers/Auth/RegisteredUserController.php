<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * FR-A1 — self-registration for Students and Staff.
 *
 * CRITICAL (FR-A5, BR-07): the role is assigned by the SERVER, always
 * student_staff. It is never taken from the request — a user cannot register
 * themselves as an Officer or Super Admin. Those accounts are seeded or
 * provisioned by an existing Super Admin.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'user_type' => ['required', Rule::in([User::TYPE_STUDENT, User::TYPE_STAFF])],
            'reg_no'    => ['required', 'string', 'max:50', 'unique:users,reg_no'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'user_type'         => $validated['user_type'],   // student | staff (identity)
            'reg_no'            => $validated['reg_no'],
            'email'             => $validated['email'],
            'phone'             => $validated['phone'] ?? null,
            'password'          => $validated['password'],    // hashed by the model cast
            'reputation_points' => 0,
            'status'            => User::STATUS_ACTIVE,
        ]);

        // Role assigned server-side — NOT from user input (BR-07).
        $user->assignRole(User::ROLE_STUDENT_STAFF);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route($user->homeRoute());
    }
}
