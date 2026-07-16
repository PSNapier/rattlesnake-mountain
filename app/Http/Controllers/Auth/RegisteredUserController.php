<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
        $validated = $request->validate([
            'referrer_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereNull('banned_at')
                        ->whereNull('deleted_at')
                        ->where('is_sanctuary', false);
                }),
            ],
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'rules_agreed' => ['required', 'accepted'],
        ]);

        $referrer = null;
        $referredByUsername = null;

        if (! empty($validated['referrer_id'])) {
            $referrer = User::query()->find($validated['referrer_id']);
            $referredByUsername = $referrer?->name;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'referred_by_username' => $referredByUsername,
        ]);

        if ($referrer !== null) {
            Referral::query()->create([
                'recruit_id' => $user->id,
                'referrer_id' => $referrer->id,
            ]);
        }

        event(new Registered($user));

        if (config('app.skip_email_verification')) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        Auth::login($user);

        return config('app.skip_email_verification')
            ? to_route('dashboard')
            : to_route('verification.notice');
    }
}
