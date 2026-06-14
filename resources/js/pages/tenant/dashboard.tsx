import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import ClubLayout from '@/layouts/club-layout';
import { Link } from '@inertiajs/react';
import { CalendarPlus, MapPinPlus, Plus, TrendingDown, TrendingUp, Trophy, UserPlus } from 'lucide-react';

interface Capabilities {
    canManageCourts: boolean;
    canManageMembers: boolean;
    canManageTournaments: boolean;
    canManageTeams: boolean;
    canBook: boolean;
}
interface Stats {
    members: number;
    courts: number;
    activeCourts: number;
    tournaments: number;
    openTournaments: number;
    teams: number;
    bookingsThisWeek: number;
    bookingsThisMonth: number;
    bookingsPrevMonth: number;
}
interface DayBookings {
    label: string;
    date: string;
    total: number;
    byCourt: { court: string; count: number }[];
}
interface HeatmapData {
    weekdays: string[];
    weeks: { date: string; count: number }[][];
    max: number;
}
interface NextTournament {
    id: number;
    name: string;
    status: string;
    starts_on: string | null;
    categories: number;
    registrations: number;
    teams: number;
    byCategory: { name: string; count: number }[];
}
interface DashboardProps {
    club: { id: string; name: string; slug: string };
    capabilities: Capabilities;
    stats: Stats;
    courtUsage: { pct: number; reservedHours: number; capacityHours: number };
    bookingsByDay: DayBookings[];
    heatmap: HeatmapData;
    bookingsByCourt: { court: string; count: number }[];
    bookingsThisYear: { month: string; count: number }[];
    upcomingBookings: { id: number; court: string | null; member: string | null; starts_at: string | null; ends_at: string | null }[];
    recentMembers: { name: string; email: string; roles: string[] }[];
    nextTournament: NextTournament | null;
}

const chartVar = (i: number) => `var(--chart-${(i % 5) + 1})`;
const fmtTime = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString(undefined, { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';

// ── Chart primitives (monochrome, dependency-free) ────────────────────────────

function Donut({ pct }: { pct: number }) {
    const r = 42;
    const c = 2 * Math.PI * r;
    const dash = (Math.min(100, Math.max(0, pct)) / 100) * c;
    return (
        <div className="relative flex size-28 shrink-0 items-center justify-center">
            <svg viewBox="0 0 100 100" className="size-28 -rotate-90">
                <circle cx="50" cy="50" r={r} fill="none" stroke="var(--muted)" strokeWidth="11" />
                <circle
                    cx="50"
                    cy="50"
                    r={r}
                    fill="none"
                    stroke="var(--foreground)"
                    strokeWidth="11"
                    strokeLinecap="round"
                    strokeDasharray={`${dash} ${c}`}
                />
            </svg>
            <span className="text-display absolute text-xl">{pct}%</span>
        </div>
    );
}

function LineChart({ data }: { data: number[] }) {
    const max = Math.max(1, ...data);
    const w = 100;
    const h = 36;
    const points = data.map((v, i) => `${(i / Math.max(1, data.length - 1)) * w},${h - (v / max) * h}`).join(' ');
    return (
        <svg viewBox={`0 0 ${w} ${h}`} preserveAspectRatio="none" className="h-14 w-full">
            <polyline points={points} fill="none" stroke="var(--foreground)" strokeWidth="1.5" vectorEffect="non-scaling-stroke" />
        </svg>
    );
}

function WeekBars({ days }: { days: DayBookings[] }) {
    const max = Math.max(1, ...days.map((d) => d.total));
    const courts = days[0]?.byCourt.map((c) => c.court) ?? [];
    const BAR_MAX = 150;

    if (days.every((d) => d.total === 0)) {
        return <p className="text-muted-foreground py-12 text-center text-sm">No bookings this week yet.</p>;
    }

    return (
        <div>
            <div className="text-muted-foreground mb-4 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                {courts.map((court, i) => (
                    <span key={court} className="inline-flex items-center gap-1.5">
                        <span className="size-2.5 rounded-sm" style={{ background: chartVar(i) }} /> {court}
                    </span>
                ))}
            </div>
            <div className="flex items-end gap-2" style={{ height: BAR_MAX + 22 }}>
                {days.map((d) => (
                    <div key={d.date} className="flex flex-1 flex-col items-center justify-end gap-2">
                        <div
                            className="flex w-7 flex-col-reverse overflow-hidden rounded-sm"
                            style={{ height: d.total > 0 ? (d.total / max) * BAR_MAX : 2 }}
                        >
                            {d.total > 0 ? (
                                d.byCourt
                                    .filter((s) => s.count > 0)
                                    .map((seg) => (
                                        <div key={seg.court} style={{ flexGrow: seg.count, background: chartVar(courts.indexOf(seg.court)) }} />
                                    ))
                            ) : (
                                <div className="bg-border h-0.5" />
                            )}
                        </div>
                        <span className="text-muted-foreground text-[10px]">{d.label}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function Heatmap({ weekdays, weeks, max }: HeatmapData) {
    const bucket = (count: number) => {
        if (count === 0) return 'bg-muted';
        const r = count / Math.max(1, max);
        if (r < 0.34) return 'bg-foreground/30';
        if (r < 0.67) return 'bg-foreground/60';
        return 'bg-foreground';
    };
    return (
        <div className="space-y-2">
            <div className="text-muted-foreground flex gap-1 pl-0 text-[10px]">
                {weekdays.map((d, i) => (
                    <span key={i} className="w-4 text-center">
                        {d}
                    </span>
                ))}
            </div>
            <div className="flex flex-col gap-1">
                {weeks.map((week, wi) => (
                    <div key={wi} className="flex gap-1">
                        {week.map((day) => (
                            <div
                                key={day.date}
                                className={`size-4 rounded-[2px] ${bucket(day.count)}`}
                                title={`${day.date}: ${day.count} booking${day.count === 1 ? '' : 's'}`}
                            />
                        ))}
                    </div>
                ))}
            </div>
            <div className="text-muted-foreground flex items-center gap-1.5 pt-1 text-[10px]">
                Less
                <span className="bg-muted size-3 rounded-[2px]" />
                <span className="bg-foreground/30 size-3 rounded-[2px]" />
                <span className="bg-foreground/60 size-3 rounded-[2px]" />
                <span className="bg-foreground size-3 rounded-[2px]" />
                More
            </div>
        </div>
    );
}

function HBars({ items }: { items: { court: string; count: number }[] }) {
    const max = Math.max(1, ...items.map((i) => i.count));
    return (
        <div className="space-y-3">
            {items.map((item) => (
                <div key={item.court} className="space-y-1">
                    <div className="flex items-center justify-between text-xs">
                        <span>{item.court}</span>
                        <span className="text-display text-muted-foreground">{item.count}</span>
                    </div>
                    <div className="bg-muted h-2 overflow-hidden rounded-full">
                        <div className="bg-foreground h-full rounded-full" style={{ width: `${(item.count / max) * 100}%` }} />
                    </div>
                </div>
            ))}
        </div>
    );
}

// ── Page ──────────────────────────────────────────────────────────────────────

function StatTile({ label, value, href }: { label: string; value: number; href: string }) {
    return (
        <Card>
            <CardContent className="flex items-center justify-between p-5">
                <div>
                    <p className="text-muted-foreground text-xs tracking-wide uppercase">{label}</p>
                    <p className="text-display mt-1 text-3xl leading-none">{value}</p>
                </div>
                <Link href={href} className="text-muted-foreground hover:text-foreground text-xs hover:underline">
                    View all
                </Link>
            </CardContent>
        </Card>
    );
}

export default function TenantDashboard(props: DashboardProps) {
    const {
        capabilities: can,
        stats,
        courtUsage,
        bookingsByDay,
        heatmap,
        bookingsByCourt,
        bookingsThisYear,
        upcomingBookings,
        recentMembers,
        nextTournament,
    } = props;

    const monthDelta =
        stats.bookingsPrevMonth > 0
            ? Math.round(((stats.bookingsThisMonth - stats.bookingsPrevMonth) / stats.bookingsPrevMonth) * 100)
            : stats.bookingsThisMonth > 0
              ? 100
              : 0;

    return (
        <ClubLayout title="Dashboard">
            {/* Quick actions */}
            <div className="mb-6 flex flex-wrap gap-2">
                {can.canBook && (
                    <Button asChild size="sm">
                        <Link href={route('bookings.index')}>
                            <CalendarPlus /> Book a court
                        </Link>
                    </Button>
                )}
                {can.canManageMembers && (
                    <Button asChild size="sm" variant="outline">
                        <Link href={route('membership.invitations.index')}>
                            <UserPlus /> Invite member
                        </Link>
                    </Button>
                )}
                {can.canManageTournaments && (
                    <Button asChild size="sm" variant="outline">
                        <Link href={route('tournaments.create')}>
                            <Trophy /> New tournament
                        </Link>
                    </Button>
                )}
                {can.canManageTeams && (
                    <Button asChild size="sm" variant="outline">
                        <Link href={route('teams.index')}>
                            <Plus /> New team
                        </Link>
                    </Button>
                )}
                {can.canManageCourts && (
                    <Button asChild size="sm" variant="outline">
                        <Link href={route('courts.index')}>
                            <MapPinPlus /> Add court
                        </Link>
                    </Button>
                )}
            </div>

            {/* Stat tiles */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatTile label="Members" value={stats.members} href={route('membership.members.index')} />
                <StatTile label="Courts" value={stats.courts} href={route('courts.index')} />
                <StatTile label="Tournaments" value={stats.tournaments} href={route('tournaments.index')} />
                <StatTile label="Teams" value={stats.teams} href={route('teams.index')} />
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-3">
                {/* Main column */}
                <div className="space-y-4 lg:col-span-2">
                    <div className="grid gap-4 sm:grid-cols-2">
                        {/* Court usage */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Court usage</CardTitle>
                                <p className="text-muted-foreground text-xs">This week</p>
                            </CardHeader>
                            <CardContent className="flex items-center gap-4">
                                <Donut pct={courtUsage.pct} />
                                <div className="text-sm">
                                    <p className="text-display text-2xl leading-none">{courtUsage.reservedHours}h</p>
                                    <p className="text-muted-foreground">booked of {courtUsage.capacityHours}h open</p>
                                    {courtUsage.capacityHours === 0 && (
                                        <Link href={route('courts.index')} className="mt-2 inline-block text-xs hover:underline">
                                            Set court hours →
                                        </Link>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Bookings this month */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Bookings</CardTitle>
                                <p className="text-muted-foreground text-xs">This month</p>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-end gap-2">
                                    <span className="text-display text-3xl leading-none">{stats.bookingsThisMonth}</span>
                                    {monthDelta !== 0 && (
                                        <span
                                            className={`mb-0.5 inline-flex items-center gap-0.5 text-xs ${monthDelta >= 0 ? 'text-foreground' : 'text-muted-foreground'}`}
                                        >
                                            {monthDelta >= 0 ? <TrendingUp className="size-3.5" /> : <TrendingDown className="size-3.5" />}
                                            {Math.abs(monthDelta)}%
                                        </span>
                                    )}
                                </div>
                                <p className="text-muted-foreground mt-1 text-xs">{stats.bookingsPrevMonth} last month</p>
                                <div className="mt-3">
                                    <LineChart data={bookingsThisYear.map((m) => m.count)} />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Bookings this week (stacked by court) */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base">Bookings this week</CardTitle>
                                <p className="text-muted-foreground text-xs">
                                    <span className="text-display">{stats.bookingsThisWeek}</span> reserved across {stats.activeCourts} court
                                    {stats.activeCourts === 1 ? '' : 's'}
                                </p>
                            </div>
                            <Button asChild size="sm" variant="ghost">
                                <Link href={route('bookings.index')}>Open bookings</Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <WeekBars days={bookingsByDay} />
                        </CardContent>
                    </Card>

                    <div className="grid gap-4 sm:grid-cols-2">
                        {/* Bookings this year */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Bookings this year</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <LineChart data={bookingsThisYear.map((m) => m.count)} />
                                <div className="text-muted-foreground mt-2 flex justify-between text-[10px]">
                                    <span>{bookingsThisYear[0]?.month}</span>
                                    <span>{bookingsThisYear[11]?.month}</span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Court usage by court */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Busiest courts</CardTitle>
                                <p className="text-muted-foreground text-xs">This month</p>
                            </CardHeader>
                            <CardContent>
                                {bookingsByCourt.length > 0 && bookingsByCourt.some((c) => c.count > 0) ? (
                                    <HBars items={bookingsByCourt} />
                                ) : (
                                    <p className="text-muted-foreground py-6 text-center text-sm">No bookings yet this month.</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Right rail */}
                <div className="space-y-4">
                    {/* Busiest days heatmap */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Busiest days</CardTitle>
                            <p className="text-muted-foreground text-xs">Last 5 weeks</p>
                        </CardHeader>
                        <CardContent>
                            <Heatmap {...heatmap} />
                        </CardContent>
                    </Card>

                    {/* Next tournament spotlight */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-base">Tournament</CardTitle>
                            {nextTournament && (
                                <Badge variant={nextTournament.status === 'open' ? 'default' : 'secondary'}>{nextTournament.status}</Badge>
                            )}
                        </CardHeader>
                        <CardContent>
                            {nextTournament ? (
                                <div className="space-y-4">
                                    <div>
                                        <Link href={route('tournaments.show', nextTournament.id)} className="font-medium hover:underline">
                                            {nextTournament.name}
                                        </Link>
                                        <p className="text-muted-foreground text-xs">{nextTournament.starts_on ?? 'Date to be set'}</p>
                                    </div>
                                    <div className="grid grid-cols-3 gap-2 text-center">
                                        <div>
                                            <p className="text-display text-xl">{nextTournament.categories}</p>
                                            <p className="text-muted-foreground text-[10px] uppercase">Categories</p>
                                        </div>
                                        <div>
                                            <p className="text-display text-xl">{nextTournament.registrations}</p>
                                            <p className="text-muted-foreground text-[10px] uppercase">Entrants</p>
                                        </div>
                                        <div>
                                            <p className="text-display text-xl">{nextTournament.teams}</p>
                                            <p className="text-muted-foreground text-[10px] uppercase">Teams</p>
                                        </div>
                                    </div>
                                    {nextTournament.byCategory.length > 0 && (
                                        <div className="space-y-2">
                                            <Separator />
                                            {nextTournament.byCategory.map((cat) => (
                                                <div key={cat.name} className="flex items-center justify-between text-sm">
                                                    <span className="text-muted-foreground">{cat.name}</span>
                                                    <span className="text-display">{cat.count}</span>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <div className="py-4 text-center">
                                    <p className="text-muted-foreground text-sm">No tournaments yet.</p>
                                    {can.canManageTournaments && (
                                        <Button asChild size="sm" variant="outline" className="mt-3">
                                            <Link href={route('tournaments.create')}>Start one</Link>
                                        </Button>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Upcoming bookings */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-base">Upcoming bookings</CardTitle>
                            <Link href={route('bookings.index')} className="text-muted-foreground hover:text-foreground text-xs hover:underline">
                                View all
                            </Link>
                        </CardHeader>
                        <CardContent>
                            {upcomingBookings.length > 0 ? (
                                <ul className="divide-border divide-y">
                                    {upcomingBookings.map((b) => (
                                        <li key={b.id} className="flex items-center justify-between gap-3 py-2.5 text-sm">
                                            <div>
                                                <p className="font-medium">{b.court}</p>
                                                <p className="text-muted-foreground text-xs">{b.member}</p>
                                            </div>
                                            <span className="text-muted-foreground text-right text-xs">{fmtTime(b.starts_at)}</span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-muted-foreground py-4 text-center text-sm">No upcoming bookings.</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent members */}
                    {recentMembers.length > 0 && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle className="text-base">Recent members</CardTitle>
                                <Link
                                    href={route('membership.members.index')}
                                    className="text-muted-foreground hover:text-foreground text-xs hover:underline"
                                >
                                    View all
                                </Link>
                            </CardHeader>
                            <CardContent>
                                <ul className="divide-border divide-y">
                                    {recentMembers.map((m) => (
                                        <li key={m.email} className="flex items-center justify-between gap-3 py-2.5 text-sm">
                                            <span className="font-medium">{m.name}</span>
                                            <span className="flex flex-wrap justify-end gap-1">
                                                {m.roles.map((r) => (
                                                    <Badge key={r} variant="outline" className="text-[10px]">
                                                        {r}
                                                    </Badge>
                                                ))}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </ClubLayout>
    );
}
