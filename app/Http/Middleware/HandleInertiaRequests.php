<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                // Spread the user's serialized attributes, then pin is_platform_admin to a
                // guaranteed boolean. The raw model omits the column whenever the instance
                // wasn't hydrated from the DB (e.g. a freshly-created model), which would
                // leave the flag undefined on the frontend; forcing it here keeps the
                // platform-admin nav gate explicit and reliable. It only ever carries the
                // current user's own flag (false for everyone but real admins).
                'user' => fn () => ($user = $request->user())
                    ? [...$user->toArray(), 'is_platform_admin' => (bool) $user->is_platform_admin]
                    : null,
            ],
            // The active club, shared on every tenant-domain request so the club shell
            // (sidebar/topbar) always has the club name without each controller passing it.
            // Null on the central domain, where tenancy isn't initialized.
            'club' => fn () => ($tenant = tenant())
                ? ['id' => $tenant->getTenantKey(), 'name' => $tenant->name, 'slug' => $tenant->slug]
                : null,

            // App branding / metadata — the single source of truth (config/branding.php).
            'branding' => config('branding'),
        ]);
    }
}
