<?php

declare(strict_types=1);

use App\Domains\Facilities\Models\Court;
use App\Http\Middleware\EnsureClubActive;
use App\Http\Middleware\ForgetTenantRouteParameter;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant (club) Routes
|--------------------------------------------------------------------------
|
| Served on <club>.<central_domain>, e.g. http://smashclub.localhost.
|
| The {tenant} wildcard only exists to give these routes a distinct route key
| from the central routes (it is NOT injected into handlers unless a handler
| declares a $tenant argument). Stancl identifies the club from the request
| host via InitializeTenancyBySubdomain, which also sets spatie's team context
| to the current club (see App\Providers\TenancyServiceProvider).
|
*/

Route::domain('{tenant}.'.config('tenancy.central_domain'))
    ->middleware([
        'web',
        InitializeTenancyBySubdomain::class,
        PreventAccessFromCentralDomains::class,
        EnsureClubActive::class, // 403 if the club is suspended
        ForgetTenantRouteParameter::class,
    ])
    ->group(function () {
        Route::middleware('auth')->group(function () {
            Route::get('/', function () {
                $club = tenant(); // App\Domains\Tenancy\Models\Tenant — the resolved club

                return Inertia::render('tenant/dashboard', [
                    'club' => [
                        'id' => $club->getTenantKey(),
                        'name' => $club->name,
                        'slug' => $club->slug,
                    ],
                    // Club-scoped roles: resolved against this tenant's team context.
                    'roles' => auth()->user()->getRoleNames(),
                    // Tenant-scoped query: BelongsToTenant limits this to the current club.
                    'courts' => Court::query()->orderBy('name')->get(['id', 'name', 'surface']),
                ]);
            })->name('tenant.dashboard');
        });

        // Per-context tenant (club) routes — each bounded context drops a file in
        // routes/tenant/. These run inside the tenant + subdomain group but NOT inside
        // 'auth', so each file applies its own middleware (usually Route::middleware('auth')).
        foreach ((array) glob(base_path('routes/tenant/*.php')) as $contextRoutes) {
            require $contextRoutes;
        }
    });
