<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Trim and convert empty string to null for referred_by_username
        $referredByUsername = $request->referred_by_username ? trim($request->referred_by_username) : null;
        $referredByUsername = $referredByUsername === '' ? null : $referredByUsername;

        $validated = $request->validate([
            'referred_by_username' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $trimmedValue = trim($value);
                    $exists = User::whereRaw('LOWER(name) = LOWER(?)', [$trimmedValue])->exists();

                    if (!$exists) {
                        $fail('The selected referred by username is invalid.');
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'rules_agreed' => ['required', 'accepted'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'referred_by_username' => $referredByUsername,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('verification.notice');
    }
}
