<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified as BaseEnsureEmailIsVerified;
use Illuminate\Http\Request;

/**
 * Aliased as `verified` so the email verification feature flag can switch the
 * framework check off without touching every protected route.
 */
class EnsureEmailIsVerified extends BaseEnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (! Features::emailVerification()) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
