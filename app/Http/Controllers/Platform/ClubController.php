<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Domains\Tenancy\Actions\ReactivateClub;
use App\Domains\Tenancy\Actions\SuspendClub;
use App\Domains\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform-admin console for managing every club from the central domain. These run
 * OUTSIDE any tenant context — `tenant()` is null here, so clubs are queried directly
 * as Tenant models (no BelongsToTenant scope) and per-club counts come from grouped
 * central-table aggregates rather than tenant-scoped Eloquent queries.
 *
 * Guarded by `auth` + EnsurePlatformAdmin (see routes/central/platform.php).
 */
class ClubController extends Controller
{
    public function index(): Response
    {
        $memberCounts = $this->countsBy('tenant_user');
        $courtCounts = $this->countsBy('courts');
        $tournamentCounts = $this->countsBy('tournaments');

        $clubs = Tenant::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status', 'created_at'])
            ->map(fn (Tenant $club) => [
                'id' => $club->getTenantKey(),
                'name' => $club->name,
                'slug' => $club->slug,
                'status' => $club->status->value,
                'createdAt' => $club->created_at?->toIso8601String(),
                'counts' => [
                    'members' => (int) ($memberCounts[$club->getTenantKey()] ?? 0),
                    'courts' => (int) ($courtCounts[$club->getTenantKey()] ?? 0),
                    'tournaments' => (int) ($tournamentCounts[$club->getTenantKey()] ?? 0),
                ],
            ])
            ->values();

        return Inertia::render('platform/clubs/index', [
            'clubs' => $clubs,
            'centralDomain' => config('tenancy.central_domain'),
        ]);
    }

    public function show(Tenant $club): Response
    {
        return Inertia::render('platform/clubs/show', [
            'club' => [
                'id' => $club->getTenantKey(),
                'name' => $club->name,
                'slug' => $club->slug,
                'status' => $club->status->value,
                'createdAt' => $club->created_at?->toIso8601String(),
                'counts' => [
                    'members' => (int) DB::table('tenant_user')->where('tenant_id', $club->getTenantKey())->count(),
                    'courts' => (int) DB::table('courts')->where('tenant_id', $club->getTenantKey())->count(),
                    'tournaments' => (int) DB::table('tournaments')->where('tenant_id', $club->getTenantKey())->count(),
                ],
            ],
            'centralDomain' => config('tenancy.central_domain'),
        ]);
    }

    public function suspend(Tenant $club, SuspendClub $suspendClub): RedirectResponse
    {
        $suspendClub->handle($club);

        return back();
    }

    public function reactivate(Tenant $club, ReactivateClub $reactivateClub): RedirectResponse
    {
        $reactivateClub->handle($club);

        return back();
    }

    /**
     * Per-tenant row counts for a central table, keyed by tenant_id.
     *
     * @return array<string, int>
     */
    private function countsBy(string $table): array
    {
        return DB::table($table)
            ->selectRaw('tenant_id, COUNT(*) as aggregate')
            ->groupBy('tenant_id')
            ->pluck('aggregate', 'tenant_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
