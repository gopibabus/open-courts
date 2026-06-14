<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Begin impersonating a club's owner.
 *
 * Approach (the simpler, well-tested path): we log the owner in on the central web guard
 * and then bounce to the club subdomain. The session cookie is shared across subdomains
 * via SESSION_DOMAIN (the central domain — `lvh.me` locally), so the owner arrives
 * authenticated on `<slug>.<central>/`. We deliberately do NOT use stancl's
 * ImpersonationToken feature here: it would require an extra migration + a token-consuming
 * route on the tenant subdomain, whereas a shared-session login is robust and needs no
 * tenant-side plumbing.
 *
 * Only platform operators reach this action (guarded by EnsurePlatformAdmin), and we
 * re-assert that flag defensively before swapping the authenticated user.
 */
class ImpersonationController extends Controller
{
    public function store(Request $request, Tenant $club): Response
    {
        abort_unless((bool) $request->user()?->is_platform_admin, 403);

        $owner = $this->resolveOwner($club);

        abort_if($owner === null, 404, 'This club has no owner to impersonate.');

        // Swap the authenticated user to the club owner on the shared central session.
        Auth::login($owner);

        return Inertia::location($this->clubUrl($request, $club->slug));
    }

    /**
     * The club's owner = the member holding the `club-admin` role in this club's team
     * context. Falls back to the earliest member if no club-admin is found.
     */
    private function resolveOwner(Tenant $club): ?User
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());

        $owner = $club->users()
            ->orderBy('tenant_user.created_at')
            ->get()
            ->first(function (User $user): bool {
                $user->unsetRelation('roles');

                return $user->hasRole('club-admin');
            });

        if ($owner !== null) {
            return $owner;
        }

        // No explicit club-admin — fall back to the first member who joined.
        return $club->users()
            ->orderBy('tenant_user.created_at')
            ->first();
    }

    /**
     * Absolute URL of the club's subdomain, preserving scheme + non-default port.
     */
    private function clubUrl(Request $request, string $slug): string
    {
        $port = $request->getPort();
        $suffix = in_array($port, [80, 443, null], true) ? '' : ':'.$port;

        return $request->getScheme().'://'.$slug.'.'.config('tenancy.central_domain').$suffix.'/';
    }
}
