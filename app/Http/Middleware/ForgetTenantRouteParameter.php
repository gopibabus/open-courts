<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Our tenant routes are constrained with Route::domain('{tenant}.<central>'), which adds
 * a `{tenant}` route parameter. Stancl identifies the tenant from the Host header (not this
 * param), so `{tenant}` is dead weight — but Laravel still passes it positionally to
 * controllers, shifting every other argument by one (so `Court $court` would receive the
 * tenant slug, and `route('x.show', $model)` would feed the model into `{tenant}`).
 *
 * Forgetting the parameter here — after subdomain identification — restores correct
 * positional binding. URL generation still works because the tenant is registered as a
 * URL default in TenancyServiceProvider.
 */
class ForgetTenantRouteParameter
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->route()?->forgetParameter('tenant');

        return $next($request);
    }
}
