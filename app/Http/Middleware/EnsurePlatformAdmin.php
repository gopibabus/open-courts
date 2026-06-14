<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the central platform-admin area. Aborts 403 unless the authenticated user is a
 * platform operator (users.is_platform_admin). Pair with the `auth` middleware so an
 * unauthenticated visitor is redirected to login first; this guard then blocks any
 * signed-in user who is not a platform admin.
 */
class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) $request->user()?->is_platform_admin, 403);

        return $next($request);
    }
}
