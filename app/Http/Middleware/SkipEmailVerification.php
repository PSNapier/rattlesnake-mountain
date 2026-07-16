<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class SkipEmailVerification extends EnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (config('app.skip_email_verification')) {
            $user = $request->user();

            if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
