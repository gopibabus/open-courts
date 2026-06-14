<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stancl\Tenancy\Exceptions\NotASubdomainException;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;

/**
 * Tenancy initialization for UNIVERSAL routes (account settings) that are served on BOTH
 * the central domain and club subdomains.
 *
 * - On a club subdomain it identifies the club from the host and initializes tenancy, so
 *   `tenant()` resolves, the `club` Inertia prop is set, the spatie team + the `{tenant}`
 *   URL default are wired (via the TenancyInitialized listener), and the page can render
 *   inside the club shell — identical to the dashboard.
 * - On the central domain (or any non-subdomain host) it is a no-op: the request continues
 *   without a tenant and the page renders in the central app shell.
 *
 * Unlike stancl's InitializeTenancyBySubdomain (which 404s off a subdomain), this never
 * fails on the central domain — that is what makes the route truly universal.
 */
final class InitializeTenancyForUniversalRoutes extends InitializeTenancyBySubdomain
{
    /**
     * @param  Request  $request
     */
    public function handle($request, Closure $next): mixed
    {
        $subdomain = $this->makeSubdomain($request->getHost());

        // Central domain / not-a-subdomain → serve centrally, no tenant.
        if ($subdomain instanceof NotASubdomainException) {
            return $next($request);
        }

        // Defensive: a stancl middleware can short-circuit with a Response.
        if ($subdomain instanceof Response) {
            return $subdomain;
        }

        return $this->initializeTenancy($request, $next, $subdomain);
    }
}
