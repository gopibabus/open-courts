<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Booking\Models\Booking;
use App\Domains\Facilities\Models\Court;
use App\Domains\Facilities\Models\CourtAvailability;
use App\Domains\Identity\Models\User;
use App\Domains\Support\Models\SupportRequest;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Actions\GenerateBracket;
use App\Domains\Tournaments\Actions\GenerateRoundRobin;
use App\Domains\Tournaments\Actions\UpdateMatchResult;
use App\Domains\Tournaments\Enums\CategoryType;
use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Enums\TournamentFormat;
use App\Domains\Tournaments\Models\Team;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed a lively, runnable demo: one platform admin and one club (Smash Tennis Club)
     * with several members, courts + opening hours, a week of bookings, and an open
     * tournament with categories, entrants and teams — enough to make the club dashboard
     * show real charts. Everything is idempotent on a fresh database.
     */
    public function run(): void
    {
        // 1. Platform operator — belongs to no single club, bypasses all checks.
        User::firstOrCreate(
            ['email' => 'admin@opentennis.test'],
            ['name' => 'Platform Admin', 'password' => Hash::make('password')],
        )->forceFill(['is_platform_admin' => true])->save();

        // 2. A demo club, reachable locally at http://smashclub.localhost.
        $club = Tenant::firstOrCreate(
            ['slug' => 'smashclub'],
            ['id' => 'smashclub', 'name' => 'Smash Tennis Club'],
        );
        $club->domains()->firstOrCreate(['domain' => 'smashclub']);

        // 3. This club's roles + permissions.
        app(RolePermissionSeeder::class)->seedForTenant($club);

        // 4. Members, courts, bookings and a tournament — all inside the tenant context so
        //    BelongsToTenant stamps tenant_id and spatie's team is pinned to this club.
        tenancy()->initialize($club);

        $members = $this->seedMembers($club);
        $courts = $this->seedCourts();
        $this->seedBookings($courts, $members);
        $this->seedTournament($members);
        $this->seedSupportRequests($members);

        tenancy()->end();
    }

    /**
     * @return array<string, User> keyed by short handle
     */
    private function seedMembers(Tenant $club): array
    {
        $people = [
            ['handle' => 'owner', 'name' => 'Sasha Owner', 'email' => 'owner@smashclub.test', 'role' => 'club-admin'],
            ['handle' => 'coach', 'name' => 'Dana Coach', 'email' => 'coach@smashclub.test', 'role' => 'coach'],
            ['handle' => 'alice', 'name' => 'Alice Rivera', 'email' => 'alice@smashclub.test', 'role' => 'member'],
            ['handle' => 'ben', 'name' => 'Ben Okafor', 'email' => 'ben@smashclub.test', 'role' => 'member'],
            ['handle' => 'chloe', 'name' => 'Chloe Park', 'email' => 'chloe@smashclub.test', 'role' => 'member'],
            ['handle' => 'omar', 'name' => 'Omar Haddad', 'email' => 'omar@smashclub.test', 'role' => 'member'],
            ['handle' => 'nina', 'name' => 'Nina Costa', 'email' => 'nina@smashclub.test', 'role' => 'member'],
            ['handle' => 'raj', 'name' => 'Raj Patel', 'email' => 'raj@smashclub.test', 'role' => 'member'],
        ];

        $members = [];
        foreach ($people as $p) {
            $user = User::firstOrCreate(['email' => $p['email']], ['name' => $p['name'], 'password' => Hash::make('password')]);
            $club->users()->syncWithoutDetaching([$user->id]);
            $user->assignRole($p['role']);
            $members[$p['handle']] = $user;
        }

        return $members;
    }

    /**
     * @return Collection<int, Court>
     */
    private function seedCourts(): Collection
    {
        $defs = [
            ['name' => 'Center Court', 'surface' => 'hard'],
            ['name' => 'Court 2', 'surface' => 'clay'],
            ['name' => 'Court 3', 'surface' => 'grass'],
        ];

        return collect($defs)->map(function (array $def) {
            $court = Court::firstOrCreate(['name' => $def['name']], ['surface' => $def['surface'], 'is_active' => true]);

            // Weekly opening hours (day_of_week 0=Mon .. 6=Sun), 08:00–21:00.
            foreach (range(0, 6) as $day) {
                CourtAvailability::firstOrCreate(
                    ['court_id' => $court->id, 'day_of_week' => $day],
                    ['opens_at' => '08:00', 'closes_at' => '21:00'],
                );
            }

            return $court;
        });
    }

    /**
     * A spread of reserved bookings: a busy current week, a few earlier this month, and a
     * trickle over the previous months so the dashboard's charts and heatmap have shape.
     *
     * @param  Collection<int, Court>  $courts
     * @param  array<string, User>  $members
     */
    private function seedBookings(Collection $courts, array $members): void
    {
        if (Booking::query()->exists()) {
            return; // already seeded — keep idempotent
        }

        $people = array_values($members);
        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        // [dayOffsetFromSundayThisWeek, hour]
        $thisWeek = [[0, 9], [0, 18], [1, 8], [1, 19], [2, 10], [2, 17], [3, 7], [3, 20], [4, 12], [4, 18], [5, 9], [5, 16], [6, 11], [6, 14]];
        foreach ($thisWeek as $i => [$dayOffset, $hour]) {
            $this->book($courts[$i % $courts->count()], $people[$i % count($people)], $weekStart->copy()->addDays($dayOffset)->setTime($hour, 0));
        }

        // Earlier this month + last month (drives the month total + delta).
        for ($i = 0; $i < 10; $i++) {
            $day = Carbon::now()->startOfMonth()->addDays(($i * 2) % 26)->setTime(8 + ($i % 10), 0);
            if ($day->lt(Carbon::now()->subDay())) {
                $this->book($courts[$i % $courts->count()], $people[$i % count($people)], $day);
            }
        }
        for ($i = 0; $i < 6; $i++) {
            $day = Carbon::now()->subMonthNoOverflow()->setDay(3 + $i * 4)->setTime(9 + ($i % 8), 0);
            $this->book($courts[$i % $courts->count()], $people[$i % count($people)], $day);
        }

        // A trickle across earlier months this year (the year line).
        for ($m = 2; $m <= 4; $m++) {
            for ($i = 0; $i < 3; $i++) {
                $day = Carbon::now()->subMonthsNoOverflow($m)->setDay(5 + $i * 6)->setTime(10 + $i * 2, 0);
                $this->book($courts[$i % $courts->count()], $people[$i % count($people)], $day);
            }
        }
    }

    private function book(Court $court, User $user, Carbon $start): void
    {
        Booking::create([
            'court_id' => $court->id,
            'user_id' => $user->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'status' => 'reserved',
        ]);
    }

    /**
     * @param  array<string, User>  $members
     */
    private function seedTournament(array $members): void
    {
        $tournament = Tournament::firstOrCreate(
            ['name' => 'Summer Slam 2026'],
            [
                'status' => 'open',
                'format' => 'single_elimination',
                'starts_on' => Carbon::now()->addDays(30)->toDateString(),
                'registration_opens_on' => Carbon::now()->subDays(3)->toDateString(),
                'registration_closes_on' => Carbon::now()->addDays(20)->toDateString(),
            ],
        );

        // Categories + entrants/teams/EC are created once. Match results are seeded
        // afterwards (idempotently) so they can be added to an already-seeded demo too.
        $singles = $tournament->categories()->firstWhere('name', "Men's Singles");

        if ($singles === null) {
            $singles = TournamentCategory::create([
                'tournament_id' => $tournament->id,
                'name' => "Men's Singles",
                'type' => CategoryType::Singles,
                'max_entrants' => 16,
            ]);
            $mixed = TournamentCategory::create([
                'tournament_id' => $tournament->id,
                'name' => 'Mixed Doubles',
                'type' => CategoryType::Mixed,
                'max_entrants' => 8,
            ]);
            // An 8-entrant event so the demo bracket is a full multi-round draw.
            $open = TournamentCategory::create([
                'tournament_id' => $tournament->id,
                'name' => 'Open Singles',
                'type' => CategoryType::Singles,
                'max_entrants' => 16,
            ]);
            // A round-robin group so the demo has a standings table.
            $ladder = TournamentCategory::create([
                'tournament_id' => $tournament->id,
                'name' => 'Club Ladder',
                'type' => CategoryType::Singles,
                'format' => TournamentFormat::RoundRobin,
            ]);

            // A handful of entrants across the categories.
            foreach (['ben', 'omar', 'coach', 'owner'] as $handle) {
                $singles->registrations()->create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $members[$handle]->id,
                    'status' => RegistrationStatus::Confirmed,
                ]);
            }
            // Doubles pairs (entrant + partner).
            foreach ([['alice', 'ben'], ['chloe', 'omar']] as [$entrant, $partner]) {
                $mixed->registrations()->create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $members[$entrant]->id,
                    'partner_id' => $members[$partner]->id,
                    'status' => RegistrationStatus::Confirmed,
                ]);
            }
            foreach (['ben', 'omar', 'coach', 'owner', 'alice', 'chloe', 'nina', 'raj'] as $handle) {
                $open->registrations()->create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $members[$handle]->id,
                    'status' => RegistrationStatus::Confirmed,
                ]);
            }
            foreach (['ben', 'omar', 'coach', 'owner'] as $handle) {
                $ladder->registrations()->create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $members[$handle]->id,
                    'status' => RegistrationStatus::Confirmed,
                ]);
            }

            // Two squads for the tournament, each with a couple of club members.
            $squads = [
                'Aces' => ['ben', 'omar'],
                'Smashers' => ['alice', 'chloe'],
            ];
            foreach ($squads as $name => $handles) {
                $team = Team::create(['tournament_id' => $tournament->id, 'name' => $name]);
                foreach ($handles as $handle) {
                    $team->players()->attach($members[$handle]->id, [
                        'tenant_id' => $team->tenant_id,
                        'tournament_id' => $tournament->id,
                    ]);
                }
            }

            // The tournament's EC (management) — the club members running it.
            foreach (['owner', 'coach'] as $handle) {
                $tournament->management()->attach($members[$handle]->id, ['tenant_id' => $tournament->tenant_id]);
            }
        }

        // Generate the demo draws across the formats.
        $this->seedBrackets($tournament, $members);
    }

    /**
     * Generate the demo draws idempotently (guarded per category):
     *  - Men's Singles — a played-out 4-player bracket (Ben champion → drives the profile demo).
     *  - Open Singles — an 8-player bracket, generated but unplayed (the full bracket visual).
     *  - Club Ladder — a round-robin played out so the standings table is populated.
     *  - Mixed Doubles — a generated doubles bracket (pairs), to show "Player & Partner".
     *
     * @param  array<string, User>  $members
     */
    private function seedBrackets(Tournament $tournament, array $members): void
    {
        $singles = $tournament->categories()->firstWhere('name', "Men's Singles");
        $open = $tournament->categories()->firstWhere('name', 'Open Singles');
        $ladder = $tournament->categories()->firstWhere('name', 'Club Ladder');
        $mixed = $tournament->categories()->firstWhere('name', 'Mixed Doubles');

        if ($singles !== null && ! TournamentMatch::where('category_id', $singles->id)->exists()) {
            app(GenerateBracket::class)->handle($singles);

            $record = app(UpdateMatchResult::class);
            $semiOne = TournamentMatch::where('category_id', $singles->id)->where('round', 'semi_final')->where('position', 0)->first();
            $semiTwo = TournamentMatch::where('category_id', $singles->id)->where('round', 'semi_final')->where('position', 1)->first();

            $record->handle($semiOne, (int) $semiOne->player_one_id, '6-3 6-4', null);  // ben beats owner
            $record->handle($semiTwo, (int) $semiTwo->player_two_id, '7-5 6-4', null);  // coach beats omar

            $final = TournamentMatch::where('category_id', $singles->id)->where('round', 'final')->first();
            $record->handle($final, $members['ben']->id, '6-4 3-6 6-2', null);          // ben beats coach
        }

        // An 8-player draw, generated and left to be played — the full bracket visual.
        if ($open !== null && ! TournamentMatch::where('category_id', $open->id)->exists()) {
            app(GenerateBracket::class)->handle($open);
        }

        // A round-robin played out (player one wins each fixture) so the standings are ranked.
        if ($ladder !== null && ! TournamentMatch::where('category_id', $ladder->id)->exists()) {
            app(GenerateRoundRobin::class)->handle($ladder);
            $record = app(UpdateMatchResult::class);
            foreach (TournamentMatch::where('category_id', $ladder->id)->orderBy('position')->get() as $fixture) {
                $record->handle($fixture, (int) $fixture->player_one_id, '6-2 6-3', null);
            }
        }

        // A doubles draw (two pairs) — generated to show "Player & Partner" in the bracket.
        if ($mixed !== null && ! TournamentMatch::where('category_id', $mixed->id)->exists()) {
            app(GenerateBracket::class)->handle($mixed);
        }
    }

    /**
     * A few help-desk requests so the support inbox / Help feature has demo data — an open
     * one and a resolved one across different topics.
     *
     * @param  array<string, User>  $members
     */
    private function seedSupportRequests(array $members): void
    {
        if (SupportRequest::query()->exists()) {
            return; // already seeded — keep idempotent
        }

        $requests = [
            ['handle' => 'alice', 'category' => 'booking', 'subject' => 'Cannot cancel my Tuesday booking', 'message' => 'The cancel button on my 7pm Court 2 booking does nothing — could you remove it for me?', 'status' => 'open'],
            ['handle' => 'ben', 'category' => 'courts', 'subject' => 'Court 3 net needs tightening', 'message' => 'The net on Court 3 is sagging in the middle. Can maintenance take a look before the weekend?', 'status' => 'open'],
            ['handle' => 'chloe', 'category' => 'tournaments', 'subject' => 'How do I join a team?', 'message' => 'I registered for Mixed Doubles but I am not sure how to get added to a squad — any pointers?', 'status' => 'closed'],
        ];

        foreach ($requests as $r) {
            SupportRequest::create([
                'user_id' => $members[$r['handle']]->id,
                'category' => $r['category'],
                'subject' => $r['subject'],
                'message' => $r['message'],
                'status' => $r['status'],
            ]);
        }
    }
}
