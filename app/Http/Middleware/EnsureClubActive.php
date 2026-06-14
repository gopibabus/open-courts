<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Tenancy\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block access to a SUSPENDED club. Intended to run on the tenant (club) route group
 * AFTER tenancy has been initialized (i.e. after InitializeTenancyBySubdomain), so that
 * `tenant()` is resolved. When the current tenant is suspended, aborts 403.
 *
 * NOTE (wiring): add this to the tenant route group in routes/tenant.php, immediately
 * after the stancl tenancy middleware:
 *
 *     ->middleware([
 *         'web',
 *         InitializeTenancyBySubdomain::class,
 *         PreventAccessFromCentralDomains::class,
 *         \App\Http\Middleware\EnsureClubActive::class,   // <-- add this
 *         ForgetTenantRouteParameter::class,
 *     ])
 *
 * It is a no-op outside a tenant context (tenant() === null), so it is safe even if
 * tenancy is not yet initialized.
 */
class EnsureClubActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Tenant|null $club */
        $club = tenant();

        if ($club !== null && $club->isSuspended()) {
            abort(403, 'This club is currently suspended.');
        }

        return $next($request);
    }
}
