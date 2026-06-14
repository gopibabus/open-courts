<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The app is served on the configured central domain (e.g. lvh.me) so that club
 * subdomains (<club>.lvh.me) resolve and share the session cookie — something
 * *.localhost cannot do. Visiting localhost / 127.0.0.1 would therefore 404 (no
 * central route matches that Host). Bounce those hosts to the canonical central
 * domain, preserving scheme, port, path, and query.
 *
 * The /up health check is exempt so container/monitoring probes still get a 200.
 */
class RedirectToCentralDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $central = config('tenancy.central_domain');
        $host = $request->getHost();

        if ($central && $host !== $central && in_array($host, ['localhost', '127.0.0.1'], true) && ! $request->is('up')) {
            $port = $request->getPort();
            $authority = $central.(in_array($port, [80, 443, null], true) ? '' : ':'.$port);

            return redirect()->to($request->getScheme().'://'.$authority.$request->getRequestUri());
        }

        return $next($request);
    }
}
