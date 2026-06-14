<?php

namespace App\Providers;

use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Platform operators bypass every authorization check, in any club context.
        // Returning null (not false) lets non-admins fall through to the normal
        // policy/permission checks.
        Gate::before(fn (User $user): ?bool => $user->is_platform_admin ? true : null);
    }
}
