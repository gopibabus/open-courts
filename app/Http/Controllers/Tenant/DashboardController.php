<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Facilities\Models\Court;
use App\Domains\Membership\Actions\BuildPlayerProfile;
use App\Domains\Tournaments\Models\Team;
use App\Domains\Tournaments\Models\Tournament;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The club (tenant) dashboard — the landing screen after a member signs in on a club
 * subdomain. It is a read-only aggregation across the booking, facilities, tournaments
 * and membership contexts, all automatically tenant-scoped by BelongsToTenant. Every
 * figure here is real club data; brand-new clubs get zeros and the UI shows empty states.
 *
 * Datetimes for the "upcoming bookings" list are serialized as NAIVE wall-clock
 * ("Y-m-d\TH:i:s", no offset) so the client renders them at the club-local hour — same
 * rule as BookingController (an offset would shift them into the browser's timezone).
 */
class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $club = tenant();

        $now = Carbon::now();
        $weekStart = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = $weekStart->copy()->addDays(7);
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->addMonth();
        $prevMonthStart = $monthStart->copy()->subMonth();
        $yearStart = $now->copy()->startOfYear();
        $yearEnd = $yearStart->copy()->addYear();

        $courts = Court::query()->with('availability')->orderBy('name')->get();
        $activeCourts = $courts->where('is_active', true);

        // ── Reserved bookings this week → stacked-by-court bar + court-usage donut ──
        $weekBookings = Booking::query()
            ->where('status', BookingStatus::Reserved)
            ->where('starts_at', '>=', $weekStart)
            ->where('starts_at', '<', $weekEnd)
            ->with('court:id,name')
            ->get(['id', 'court_id', 'starts_at', 'ends_at']);

        $courtNames = $activeCourts->pluck('name')->values();

        $bookingsByDay = collect(range(0, 6))->map(function (int $i) use ($weekStart, $weekBookings, $courtNames) {
            $day = $weekStart->copy()->addDays($i);
            $forDay = $weekBookings->filter(fn (Booking $b) => $b->starts_at->isSameDay($day));

            return [
                'label' => $day->format('D'),
                'date' => $day->format('j M'),
                'total' => $forDay->count(),
                'byCourt' => $courtNames->map(fn (string $name) => [
                    'court' => $name,
                    'count' => $forDay->filter(fn (Booking $b) => $b->court?->name === $name)->count(),
                ])->values(),
            ];
        })->values();

        // Court-usage donut: reserved hours this week vs the weekly capacity (sum of all
        // active courts' weekly opening windows). Guard against clubs with no availability set.
        // abs() because Carbon 3's diffInMinutes is signed ($b - $a), unlike Carbon 2's absolute.
        $capacityHours = $activeCourts->sum(fn (Court $court) => $court->availability->sum(function ($w) {
            return ($w->opens_at && $w->closes_at) ? abs($w->opens_at->diffInMinutes($w->closes_at)) / 60 : 0;
        }));
        $reservedHours = $weekBookings->sum(fn (Booking $b) => abs($b->starts_at->diffInMinutes($b->ends_at)) / 60);
        $usagePct = $capacityHours > 0 ? min(100, (int) round($reservedHours / $capacityHours * 100)) : 0;

        // ── Bookings this month vs last (the trend card) ──
        $bookingsThisMonth = $this->reservedBetween($monthStart, $monthEnd);
        $bookingsPrevMonth = $this->reservedBetween($prevMonthStart, $monthStart);

        // ── Heatmap: reserved bookings per day over the last 5 weeks (Sun–Sat grid) ──
        $heatStart = $weekStart->copy()->subWeeks(4);
        $heatByDate = Booking::query()
            ->where('status', BookingStatus::Reserved)
            ->where('starts_at', '>=', $heatStart)
            ->where('starts_at', '<', $weekEnd)
            ->get(['starts_at'])
            ->groupBy(fn (Booking $b) => $b->starts_at->toDateString())
            ->map->count();

        $heatWeeks = collect(range(0, 4))->map(fn (int $w) => collect(range(0, 6))->map(function (int $d) use ($heatStart, $w, $heatByDate) {
            $date = $heatStart->copy()->addDays($w * 7 + $d)->toDateString();

            return ['date' => $date, 'count' => (int) ($heatByDate[$date] ?? 0)];
        })->values())->values();

        // ── Bookings per month this year (the line) ──
        $yearByMonth = Booking::query()
            ->where('status', BookingStatus::Reserved)
            ->where('starts_at', '>=', $yearStart)
            ->where('starts_at', '<', $yearEnd)
            ->get(['starts_at'])
            ->groupBy(fn (Booking $b) => (int) $b->starts_at->format('n'))
            ->map->count();

        $bookingsThisYear = collect(range(1, 12))->map(fn (int $m) => [
            'month' => Carbon::create(null, $m, 1)->format('M'),
            'count' => (int) ($yearByMonth[$m] ?? 0),
        ])->values();

        // ── Bookings per court this month (horizontal bars) ──
        $monthByCourt = Booking::query()
            ->where('status', BookingStatus::Reserved)
            ->where('starts_at', '>=', $monthStart)
            ->where('starts_at', '<', $monthEnd)
            ->get(['court_id'])
            ->groupBy('court_id')
            ->map->count();

        $bookingsByCourt = $courts->map(fn (Court $c) => [
            'court' => $c->name,
            'count' => (int) ($monthByCourt[$c->id] ?? 0),
        ])->sortByDesc('count')->values();

        // ── Upcoming reserved bookings (the list) ──
        $upcomingBookings = Booking::query()
            ->where('status', BookingStatus::Reserved)
            ->where('starts_at', '>=', $now)
            ->orderBy('starts_at')
            ->with(['court:id,name', 'user:id,name'])
            ->limit(6)
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'court' => $b->court?->name,
                'member' => $b->user?->name,
                'starts_at' => $b->starts_at?->format('Y-m-d\TH:i:s'),
                'ends_at' => $b->ends_at?->format('Y-m-d\TH:i:s'),
            ]);

        // ── The signed-in member's own activity (personal section) ──
        $myUpcoming = Booking::query()
            ->where('status', BookingStatus::Reserved)
            ->where('user_id', $user->id)
            ->where('starts_at', '>=', $now)
            ->orderBy('starts_at')
            ->with('court:id,name')
            ->limit(5)
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'court' => $b->court?->name,
                'starts_at' => $b->starts_at?->format('Y-m-d\TH:i:s'),
                'ends_at' => $b->ends_at?->format('Y-m-d\TH:i:s'),
            ]);

        // Reuse the player-profile derivation for the member's competitive record + activity.
        $profile = app(BuildPlayerProfile::class)->handle($club, $user);
        $you = [
            'id' => $user->id,
            'name' => $user->name,
            'reservations' => $myUpcoming->values(),
            'stats' => [
                'upcoming' => $myUpcoming->count(),
                'bookings' => $profile['activity']['bookings'],
                'tournaments' => $profile['activity']['tournaments'],
                'played' => $profile['record']['played'],
                'won' => $profile['record']['won'],
                'titles' => $profile['record']['titles'],
            ],
        ];

        // ── Recent members (with their club-scoped roles) ──
        $recentMembers = $club->users()
            ->orderByDesc('users.id')
            ->limit(5)
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn ($u) => [
                'name' => $u->name,
                'email' => $u->email,
                'roles' => $u->getRoleNames()->values(),
            ]);

        // ── Spotlight tournament (soonest open one, else the latest) + its squads ──
        $tournament = Tournament::query()->where('status', 'open')->withCount(['categories', 'registrations', 'teams'])->orderBy('starts_on')->first()
            ?? Tournament::query()->withCount(['categories', 'registrations', 'teams'])->latest('id')->first();

        $nextTournament = $tournament ? [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'status' => $tournament->status,
            'starts_on' => $tournament->starts_on?->toDateString(),
            'categories' => $tournament->categories_count,
            'registrations' => $tournament->registrations_count,
            'teams' => $tournament->teams_count,
            'byCategory' => $tournament->categories()->withCount('registrations')->orderBy('name')->get()
                ->map(fn ($c) => ['name' => $c->name, 'count' => $c->registrations_count])->values(),
        ] : null;

        return Inertia::render('tenant/dashboard', [
            'club' => ['id' => $club->getTenantKey(), 'name' => $club->name, 'slug' => $club->slug],
            'capabilities' => [
                'canManageCourts' => $user->can('court.manage'),
                'canManageMembers' => $user->can('member.manage'),
                'canManageTournaments' => $user->can('tournament.manage'),
                'canManageTeams' => $user->can('team.manage'),
                'canBook' => $user->can('court.book'),
            ],
            'stats' => [
                'members' => $club->users()->count(),
                'courts' => $courts->count(),
                'activeCourts' => $activeCourts->count(),
                'tournaments' => Tournament::query()->count(),
                'openTournaments' => Tournament::query()->where('status', 'open')->count(),
                'teams' => Team::query()->count(),
                'bookingsThisWeek' => $weekBookings->count(),
                'bookingsThisMonth' => $bookingsThisMonth,
                'bookingsPrevMonth' => $bookingsPrevMonth,
            ],
            'courtUsage' => ['pct' => $usagePct, 'reservedHours' => round($reservedHours, 1), 'capacityHours' => round($capacityHours, 1)],
            'bookingsByDay' => $bookingsByDay,
            'heatmap' => ['weekdays' => ['S', 'M', 'T', 'W', 'T', 'F', 'S'], 'weeks' => $heatWeeks, 'max' => (int) ($heatByDate->max() ?? 0)],
            'bookingsByCourt' => $bookingsByCourt,
            'bookingsThisYear' => $bookingsThisYear,
            'upcomingBookings' => $upcomingBookings,
            'recentMembers' => $recentMembers,
            'nextTournament' => $nextTournament,
            'you' => $you,
        ]);
    }

    private function reservedBetween(Carbon $from, Carbon $to): int
    {
        return Booking::query()
            ->where('status', BookingStatus::Reserved)
            ->where('starts_at', '>=', $from)
            ->where('starts_at', '<', $to)
            ->count();
    }
}
