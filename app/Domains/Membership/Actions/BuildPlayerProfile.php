<?php

declare(strict_types=1);

namespace App\Domains\Membership\Actions;

use App\Domains\Booking\Models\Booking;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Enums\MatchRound;
use App\Domains\Tournaments\Models\Registration;
use App\Domains\Tournaments\Models\TournamentMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Assembles a player's profile for the current club: bio + activity, a competitive record
 * (played / won / lost / titles) derived from recorded matches, a trophy case (best
 * placement per tournament-category) and earned milestone badges.
 *
 * Reads tenant-scoped models, so it must run inside the club's tenancy context. The match
 * query groups the OR so the BelongsToTenant global scope is not broken by precedence.
 */
final class BuildPlayerProfile
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Tenant $club, User $member): array
    {
        $tenantId = $club->getTenantKey();

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        $member->unsetRelation('roles');

        $memberSince = DB::table('tenant_user')
            ->where('tenant_id', $tenantId)
            ->where('user_id', $member->id)
            ->value('created_at');

        /** @var Collection<int, TournamentMatch> $matches */
        $matches = TournamentMatch::query()
            ->with(['tournament:id,name', 'category:id,name', 'playerOne:id,name', 'playerTwo:id,name'])
            ->where(fn ($q) => $q->where('player_one_id', $member->id)->orWhere('player_two_id', $member->id))
            ->orderByDesc('played_at')
            ->get();

        $played = $matches->count();
        $won = $matches->where('winner_id', $member->id)->count();
        $titles = $matches->filter(fn (TournamentMatch $m) => $m->round === MatchRound::Final && $m->winner_id === $member->id)->count();
        $finals = $matches->filter(fn (TournamentMatch $m) => $m->round === MatchRound::Final)->count();

        $trophies = $matches
            ->groupBy(fn (TournamentMatch $m) => $m->tournament_id.'-'.$m->category_id)
            ->map(function (Collection $group) use ($member): ?array {
                $placement = $this->placement($group, (int) $member->id);
                if ($placement === null) {
                    return null;
                }
                $first = $group->first();

                return [
                    'tournament' => $first->tournament?->name,
                    'tournamentId' => $first->tournament_id,
                    'category' => $first->category?->name,
                    'placement' => $placement,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'roles' => $member->getRoleNames()->values()->all(),
            'memberSince' => $memberSince ? Carbon::parse($memberSince)->toIso8601String() : null,
            'record' => [
                'played' => $played,
                'won' => $won,
                'lost' => $played - $won,
                'winPct' => $played > 0 ? (int) round($won / $played * 100) : 0,
                'titles' => $titles,
                'finals' => $finals,
            ],
            'trophies' => $trophies,
            'badges' => $this->badges($won, $titles),
            'activity' => [
                'tournaments' => Registration::where('user_id', $member->id)->distinct()->count('tournament_id'),
                'teams' => DB::table('team_player')->where('tenant_id', $tenantId)->where('user_id', $member->id)->count(),
                'bookings' => Booking::where('user_id', $member->id)->count(),
            ],
            'recentResults' => $matches->take(8)->map(fn (TournamentMatch $m) => [
                'id' => $m->id,
                'tournament' => $m->tournament?->name,
                'category' => $m->category?->name,
                'round' => $m->round->label(),
                'won' => $m->winner_id === $member->id,
                'opponent' => ($m->player_one_id === $member->id ? $m->playerTwo : $m->playerOne)?->name,
                'score' => $m->score,
            ])->values()->all(),
        ];
    }

    /**
     * The member's best placement within one tournament-category group, or null if they
     * didn't reach the podium (champion → runner-up → semi-finalist).
     *
     * @param  Collection<int, TournamentMatch>  $group
     */
    private function placement(Collection $group, int $memberId): ?string
    {
        if ($group->contains(fn (TournamentMatch $m) => $m->round === MatchRound::Final && $m->winner_id === $memberId)) {
            return 'champion';
        }
        if ($group->contains(fn (TournamentMatch $m) => $m->round === MatchRound::Final && $m->winner_id !== $memberId)) {
            return 'runner_up';
        }
        if ($group->contains(fn (TournamentMatch $m) => $m->round === MatchRound::SemiFinal)) {
            return 'semi_finalist';
        }

        return null;
    }

    /**
     * Earned milestone badges (only the ones reached are returned).
     *
     * @return array<int, array{key: string, label: string}>
     */
    private function badges(int $won, int $titles): array
    {
        $defs = [
            ['key' => 'first_win', 'label' => 'First win', 'earned' => $won >= 1],
            ['key' => 'ten_wins', 'label' => '10 wins', 'earned' => $won >= 10],
            ['key' => 'twentyfive_wins', 'label' => '25 wins', 'earned' => $won >= 25],
            ['key' => 'first_title', 'label' => 'First title', 'earned' => $titles >= 1],
            ['key' => 'triple_crown', 'label' => 'Triple crown', 'earned' => $titles >= 3],
        ];

        return array_values(array_map(
            fn (array $b) => ['key' => $b['key'], 'label' => $b['label']],
            array_filter($defs, fn (array $b) => $b['earned']),
        ));
    }
}
