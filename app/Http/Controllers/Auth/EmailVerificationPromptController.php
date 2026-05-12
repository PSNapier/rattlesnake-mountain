<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the email verification prompt page.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        if ($request->user()->hasVerifiedEmail() || env('SKIP_EMAIL_VERIFICATION', false)) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return Inertia::render('auth/VerifyEmail', ['status' => $request->session()->get('status')]);
    }
}
