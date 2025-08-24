<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DevPasswordProtection
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if no dev password is set
        if (! config('app.dev_password')) {
            return $next($request);
        }

        // Skip the password entry route to avoid infinite redirect
        if ($request->is('dev-password')) {
            return $next($request);
        }

        // Check if user is authenticated with dev password
        if ($request->session()->get('dev_authenticated') === true) {
            return $next($request);
        }

        // Redirect to password entry
        return redirect()->route('dev-password');
    }
}
