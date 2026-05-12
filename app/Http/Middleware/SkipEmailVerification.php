<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SkipEmailVerification extends EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        if (config('app.skip_email_verification')) {
            $user = $request->user();

            if ($user && ! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            return $next($request);
        }

        return parent::handle($request, $next, ...$guards);
    }
}
