<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitUploads
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure user is authenticated
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        $key = 'uploads:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 20)) { // 20 uploads per hour
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "Too many uploads. Please wait {$seconds} seconds before trying again.",
            ], 429);
        }

        RateLimiter::hit($key, 3600); // 1 hour window

        return $next($request);
    }
}
