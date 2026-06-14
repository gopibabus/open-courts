import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarClock, Check, ChevronDown, Lock, Minus, ShieldCheck } from 'lucide-react';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { type Appearance, useAppearance } from '@/hooks/use-appearance';

/*
 * OpenTennis marketing home page — rendered at route('home') on the central domain.
 * Design: "Spec Sheet" (vask.dev-inspired) — monochrome, JetBrains Mono throughout,
 * Doto dot-matrix (.text-display) reserved STRICTLY for numerals + the wordmark.
 * Every capability is shown via a buildable div-only mock (no screenshots), captioned
 * with FIG.0x / FEAT.0x documentation labels. Color appears only for state.
 */

// ── Reused pieces ────────────────────────────────────────────────────────────

/** The light/dark/system segmented toggle, matching the /ui design-system gallery. */
function ThemeToggle() {
    const { appearance, updateAppearance } = useAppearance();
    const modes: Appearance[] = ['light', 'dark', 'system'];
    return (
        <div className="border-border flex items-center gap-1 rounded-md border p-0.5" role="group" aria-label="Theme">
            {modes.map((mode) => (
                <button
                    key={mode}
                    type="button"
                    aria-pressed={appearance === mode}
                    onClick={() => updateAppearance(mode)}
                    className={`focus-visible:ring-ring rounded px-2.5 py-1 text-xs capitalize transition-colors focus-visible:ring-2 focus-visible:outline-none ${
                        appearance === mode ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'
                    }`}
                >
                    {mode}
                </button>
            ))}
        </div>
    );
}

/** A FIG.0x / FEAT.0x documentation eyebrow. */
function Eyebrow({ children }: { children: React.ReactNode }) {
    return <p className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">{children}</p>;
}

/** A tiny monospace status indicator: filled dot + optional lowercase label. */
function StatusDot({ label }: { label?: string }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            <span className="bg-foreground size-1.5 rounded-full" />
            {label ? <span className="text-muted-foreground text-[11px]">{label}</span> : null}
        </span>
    );
}

/** A label → value spec row (muted label left, mono/display value right). */
function SpecRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 py-2 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right">{value}</span>
        </div>
    );
}

// ── The hero "live court board" mock (pure divs + .text-display numerals) ───────

const BOARD_TIMES = ['08:00', '10:00', '12:00', '14:00', '16:00'];
const BOARD_COURTS = ['01', '02', '03', '04'];
type Cell = 'free' | 'booked' | 'mine' | 'blocked';
// rows = courts 01–04, cols = the times above
const BOARD: Cell[][] = [
    ['booked', 'free', 'booked', 'mine', 'free'],
    ['free', 'booked', 'free', 'booked', 'booked'],
    ['booked', 'free', 'blocked', 'free', 'booked'],
    ['free', 'booked', 'free', 'free', 'booked'],
];

const STRIPE = '[background:repeating-linear-gradient(45deg,var(--foreground)_0,var(--foreground)_2px,transparent_2px,transparent_5px)]';

function cellClass(state: Cell): string {
    switch (state) {
        case 'booked':
            return 'bg-foreground';
        case 'mine':
            return `border border-border ${STRIPE}`;
        case 'blocked':
            return 'bg-card ring-2 ring-destructive ring-inset';
        default:
            return 'bg-muted';
    }
}

function CourtBoard() {
    return (
        <div className="border-border bg-card rounded-lg border p-4">
            <div className="mb-3 flex items-center justify-between">
                <span className="text-muted-foreground text-[11px] tracking-[0.2em] uppercase">FIG.01 · Centre Court · Tue</span>
                <StatusDot label="live" />
            </div>
            <div
                className="grid grid-cols-[auto_repeat(5,1fr)] gap-1.5"
                role="img"
                aria-label="Example court board: filled cells are reserved, light cells are available, the striped cell is your booking, and the red-outlined cell is blocked for maintenance."
            >
                <div />
                {BOARD_TIMES.map((t) => (
                    <div key={t} className="text-display text-muted-foreground text-center text-[11px]">
                        {t}
                    </div>
                ))}
                {BOARD_COURTS.map((court, r) => (
                    <div key={court} className="contents">
                        <div className="text-display text-muted-foreground flex items-center pr-1 text-sm">{court}</div>
                        {BOARD[r].map((state, c) => (
                            <div key={c} className={`h-8 rounded-sm ${cellClass(state)}`} aria-hidden />
                        ))}
                    </div>
                ))}
            </div>
            <div className="text-muted-foreground mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px]">
                <span className="inline-flex items-center gap-1.5">
                    <span className="bg-muted size-3 rounded-sm" /> available
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="bg-foreground size-3 rounded-sm" /> reserved
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className={`border-border size-3 rounded-sm border ${STRIPE}`} /> your booking
                </span>
                <span className="text-foreground ml-auto font-medium">+ double-booking prevented</span>
            </div>
        </div>
    );
}

/** The /ui dot-matrix scoreboard strip, reused on the tournaments section. */
function Scoreboard() {
    return (
        <Card>
            <CardContent className="flex flex-wrap items-end justify-between gap-8 pt-6">
                <div>
                    <div className="text-muted-foreground text-xs">Final · Centre Court</div>
                    <div className="text-display mt-1 text-4xl leading-none sm:text-5xl">6—4 7—5</div>
                </div>
                <div className="text-right">
                    <div className="text-muted-foreground text-xs">Court</div>
                    <div className="text-display text-4xl leading-none sm:text-5xl">01</div>
                </div>
                <div className="text-right">
                    <div className="text-muted-foreground text-xs">Starts in</div>
                    <div className="text-display text-4xl leading-none sm:text-5xl">12:30</div>
                </div>
            </CardContent>
        </Card>
    );
}

// ── Static content ────────────────────────────────────────────────────────────

const NAV = [
    { href: '#booking', label: 'Booking' },
    { href: '#tournaments', label: 'Tournaments' },
    { href: '#teams-members', label: 'People' },
    { href: '#compare', label: 'Compare' },
    { href: '#faq', label: 'FAQ' },
];

const BOOKING_CHECKS = [
    'Live, per-court availability',
    'Weekly opening windows you define',
    'Blackout dates for maintenance & events',
    'Member self-service booking + cancellation',
    'Automatic double-booking prevention',
];

const TOURNAMENT_CATEGORIES = [
    { name: "Men's Singles", entrants: '24' },
    { name: "Women's Singles", entrants: '16' },
    { name: 'Mixed Doubles', entrants: '12' },
];

// The real club role → permission matrix (database/seeders/RolePermissionSeeder::roleMatrix()).
const PERMISSIONS = ['club.manage', 'member.manage', 'court.manage', 'court.book', 'booking.manage', 'tournament.manage', 'team.manage'] as const;
const ROLE_MATRIX: { role: string; grants: string[] }[] = [
    { role: 'Owner / Club-admin', grants: [...PERMISSIONS] },
    { role: 'Coach', grants: ['court.book', 'tournament.manage', 'team.manage'] },
    { role: 'Member', grants: ['court.book'] },
];

const ROSTER = [
    { seed: '01', name: 'A. Petrova', tag: 'captain' },
    { seed: '02', name: 'J. Okafor', tag: '' },
    { seed: '03', name: 'M. Lindqvist', tag: '' },
    { seed: '04', name: 'R. Haddad', tag: '' },
];

const SUBDOMAINS = ['smashtennis.opentennis', 'baseline-club.opentennis', 'cityfederation.opentennis'];

const COMPARE: { cap: string; before: string; after: string }[] = [
    { cap: 'Double-booking', before: 'happens, then arguments', after: 'impossible at write time' },
    { cap: 'Court availability', before: 'whoever updated last', after: 'live, always current' },
    { cap: 'Member sign-up', before: 'you retype it', after: 'members self-serve' },
    { cap: 'Tournament registration', before: 'reply-all threads', after: 'open / close in one tap' },
    { cap: 'Roles & access', before: 'everyone can edit everything', after: 'granular, club-scoped' },
    { cap: 'Per-club data isolation', before: 'one shared file', after: 'isolated subdomain, row-level' },
    { cap: 'Accountability', before: 'who deleted the row?', after: 'every action attributed' },
    { cap: 'On a phone at the club', before: 'pinch-zoom a grid', after: 'built for it' },
];

const STATS = [
    { n: '00', t: 'double-bookings possible', s: 'conflicts that reach the court' },
    { n: '24/7', t: 'member self-service', s: 'the booking line never closes' },
    { n: '1', t: 'subdomain per club', s: 'fully isolated, branded' },
    { n: '100%', t: 'of actions attributed', s: 'every change has a name' },
];

const CONSOLE = [
    { name: 'Smash Tennis Club', status: 'active' as const, action: 'Impersonate' },
    { name: 'Baseline Club', status: 'active' as const, action: 'Impersonate' },
    { name: 'Old Town LTC', status: 'suspended' as const, action: 'Reactivate' },
];

const BUILD_CHIPS = [
    'row-level multi-tenancy',
    'DDD + after-commit events',
    'monochrome, mono-typed UI',
    'Pest + Playwright',
    'Dockerised — one command up',
];

const FAQ = [
    {
        q: 'Is my club’s data separate from other clubs?',
        a: 'Yes. Every club lives on its own subdomain with data isolated at the database row level. No club can see, query or touch another’s members, bookings or tournaments.',
    },
    {
        q: 'Do my members need to install anything?',
        a: 'No. They open yourclub.opentennis in any browser and book on their phone. No app store, no downloads.',
    },
    {
        q: 'Can two people book the same court?',
        a: 'No. The second booking is rejected the instant it’s attempted. Double-booking is impossible by design, not by reminder.',
    },
    {
        q: 'Can I control who does what?',
        a: 'Yes. Owners and admins run everything; coaches book and run tournaments and teams; members book. Permissions are granular and scoped to your club.',
    },
    {
        q: 'What if I’m already using a spreadsheet?',
        a: 'Perfect — you’ve done the hard part. Bring your courts and members over; setup takes minutes, not a migration project.',
    },
    {
        q: 'Who owns the data?',
        a: 'You do. It’s your club’s directory, bookings and tournaments. We host and isolate it; we don’t repurpose it.',
    },
];

/** A full-width section wrapper with a top hairline + documentation label. */
function Section({ id, label, children }: { id?: string; label: React.ReactNode; children: React.ReactNode }) {
    return (
        <section id={id} className="border-border scroll-mt-20 border-t">
            <div className="mx-auto max-w-6xl px-6 py-20 md:py-24">
                <Eyebrow>{label}</Eyebrow>
                <div className="mt-6">{children}</div>
            </div>
        </section>
    );
}

// ── Page ───────────────────────────────────────────────────────────────────────

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="OpenTennis — run your tennis club like software" />

            <div className="bg-background text-foreground min-h-screen scroll-smooth">
                {/* Sticky header */}
                <header className="border-border bg-background/80 sticky top-0 z-50 border-b backdrop-blur">
                    <div className="mx-auto flex h-14 max-w-6xl items-center justify-between px-6">
                        <Link href={route('home')} className="text-display text-xl">
                            OPEN·TENNIS
                        </Link>
                        <nav className="text-muted-foreground hidden items-center gap-6 text-sm md:flex">
                            {NAV.map((n) => (
                                <a key={n.href} href={n.href} className="hover:text-foreground transition-colors">
                                    {n.label}
                                </a>
                            ))}
                        </nav>
                        <div className="flex items-center gap-2">
                            <ThemeToggle />
                            {auth?.user ? (
                                <Button size="sm" asChild>
                                    <Link href={route('dashboard')}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" size="sm" asChild className="hidden sm:inline-flex">
                                        <Link href={route('login')}>Sign in</Link>
                                    </Button>
                                    <Button size="sm" asChild>
                                        <Link href={route('register-club.create')}>Start your club</Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                {/* Hero */}
                <section className="mx-auto max-w-6xl px-6 pt-16 pb-12">
                    <div className="grid items-center gap-12 lg:grid-cols-2">
                        <div className="space-y-6">
                            <Eyebrow>FIG.00 — for tennis clubs & federations</Eyebrow>
                            <h1 className="text-4xl leading-[1.05] font-semibold tracking-tight md:text-5xl">
                                Run your tennis club like software, not spreadsheets.
                            </h1>
                            <p className="text-muted-foreground max-w-prose text-base md:text-lg">
                                Court bookings, tournaments, teams and members — each club on its own branded subdomain, fully isolated.
                                Double-bookings are impossible by design, and members self-serve so you stop being the booking line.
                            </p>
                            <div className="flex flex-wrap gap-3">
                                <Button size="lg" asChild>
                                    <Link href={route('register-club.create')}>Start your club</Link>
                                </Button>
                                <Button size="lg" variant="outline" asChild>
                                    <a href="#how">See how it works</a>
                                </Button>
                            </div>
                            <div className="text-muted-foreground flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                                <StatusDot label="Row-level tenant isolation" />
                                <StatusDot label="Members self-serve 24/7" />
                                <StatusDot label="Built mono, tested, Dockerised" />
                            </div>
                        </div>
                        <CourtBoard />
                    </div>
                </section>

                {/* FIG.01 — How it works */}
                <Section id="how" label="FIG.01 — how it works">
                    <div className="max-w-prose space-y-3">
                        <h2 className="text-3xl font-semibold tracking-tight">One subdomain. One source of truth.</h2>
                        <p className="text-muted-foreground">
                            Most clubs run on a spreadsheet someone forgets to update and a group chat nobody reads. OpenTennis gives every club its
                            own branded address — yourclub.opentennis — with its own members, courts and data, walled off from every other club at the
                            database row level.
                        </p>
                    </div>
                    <div className="mt-10 grid items-center gap-8 md:grid-cols-[1fr_auto_1fr]">
                        {/* BEFORE */}
                        <div className="grid grid-cols-2 gap-3">
                            {['WhatsApp group', 'Booking spreadsheet', 'Paper sign-up sheet', 'DMs to the coach'].map((n) => (
                                <div key={n} className="border-border bg-muted text-muted-foreground rounded-md border px-3 py-4 text-center text-xs">
                                    {n}
                                </div>
                            ))}
                        </div>
                        {/* arrow */}
                        <div className="text-muted-foreground flex flex-col items-center gap-2">
                            <span className="text-display text-2xl">→</span>
                            <span className="text-[11px] tracking-[0.15em] uppercase">one subdomain</span>
                        </div>
                        {/* AFTER */}
                        <div className="bg-foreground text-background rounded-lg p-5">
                            <div className="mb-3 flex items-center gap-2 text-sm font-medium">
                                <Lock className="size-3.5" /> yourclub.opentennis
                            </div>
                            <div className="space-y-2 text-sm">
                                {['Courts', 'Tournaments', 'Teams', 'Members'].map((row) => (
                                    <div key={row} className="border-background/20 flex items-center justify-between border-t pt-2">
                                        <span>{row}</span>
                                        <span className="bg-background size-1.5 rounded-full" />
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                    <p className="mt-6 text-sm">
                        <span className="text-muted-foreground line-through">− 4 tools, 0 sources of truth</span>{' '}
                        <span className="ml-3 font-medium">+ 1 subdomain, fully isolated</span>
                    </p>
                </Section>

                {/* FEAT.01 — Court booking */}
                <Section id="booking" label="FEAT.01 — court booking">
                    <div className="grid items-center gap-12 md:grid-cols-2">
                        <div className="space-y-5">
                            <h2 className="text-3xl font-semibold tracking-tight">Booking that can’t double-book itself.</h2>
                            <p className="text-muted-foreground">
                                Set weekly opening windows per court, block out maintenance days, and let members book and cancel their own slots. The
                                instant two people reach for the same court, OpenTennis rejects the second — at write time. You never arbitrate a
                                double-booking again.
                            </p>
                            <ul className="space-y-2">
                                {BOOKING_CHECKS.map((c) => (
                                    <li key={c} className="flex items-center gap-2 text-sm">
                                        <Check className="text-muted-foreground size-4 shrink-0" /> {c}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="space-y-4">
                            <Card className="max-w-sm">
                                <CardHeader className="flex flex-row items-center justify-between">
                                    <CardTitle className="text-base">Court 03</CardTitle>
                                    <Badge variant="outline">hard</Badge>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <Separator />
                                    <SpecRow label="Next free" value={<span className="text-display text-lg">14:00</span>} />
                                </CardContent>
                                <CardFooter>
                                    <Button className="w-full">Book</Button>
                                </CardFooter>
                            </Card>
                            <Alert>
                                <CalendarClock className="size-4" />
                                <AlertTitle>Slot just taken</AlertTitle>
                                <AlertDescription>That 14:00 was booked one second ago — pick another time.</AlertDescription>
                            </Alert>
                        </div>
                    </div>
                </Section>

                {/* FEAT.02 — Tournaments */}
                <Section id="tournaments" label="FEAT.02 — tournaments">
                    <div className="grid items-center gap-12 md:grid-cols-2">
                        <div className="order-2 space-y-4 md:order-1">
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between">
                                    <CardTitle className="text-base">Spring Open 2026</CardTitle>
                                    <Badge>registration open</Badge>
                                </CardHeader>
                                <CardContent className="divide-border divide-y">
                                    {TOURNAMENT_CATEGORIES.map((cat) => (
                                        <div key={cat.name} className="flex items-center justify-between py-2.5 text-sm">
                                            <span className="inline-flex items-center gap-2">
                                                <StatusDot /> {cat.name}
                                            </span>
                                            <span className="text-display text-lg">{cat.entrants}</span>
                                        </div>
                                    ))}
                                    <div className="space-y-1.5 pt-3">
                                        <div className="text-muted-foreground flex items-center justify-between text-xs">
                                            <span>registered</span>
                                            <span className="text-display text-foreground text-sm">24 / 32</span>
                                        </div>
                                        <div className="bg-muted h-1.5 w-full overflow-hidden rounded-full">
                                            <div className="bg-foreground h-full w-3/4" />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <Scoreboard />
                        </div>
                        <div className="order-1 space-y-5 md:order-2">
                            <h2 className="text-3xl font-semibold tracking-tight">
                                Open registration, fill the draw, close it — without a single email thread.
                            </h2>
                            <p className="text-muted-foreground">
                                Spin up a tournament, add categories like Men’s Singles or Mixed Doubles, then open registration to your members. They
                                self-register and withdraw from their own account; you watch the draw fill in real time and close it with one switch.
                            </p>
                            <div className="divide-border divide-y">
                                <SpecRow label="Categories" value="unlimited" />
                                <SpecRow label="Registration" value="open / close on demand" />
                                <SpecRow label="Sign-up" value="member self-service" />
                                <SpecRow label="Withdrawals" value="self-service" />
                            </div>
                        </div>
                    </div>
                </Section>

                {/* FEAT.03 + FEAT.04 — Teams & rosters · Members & roles */}
                <Section id="teams-members" label="access control">
                    <div className="max-w-prose space-y-3">
                        <h2 className="text-3xl font-semibold tracking-tight">Everyone gets exactly the keys they need.</h2>
                    </div>
                    <div className="mt-8 grid gap-6 md:grid-cols-2">
                        {/* Teams & rosters */}
                        <Card>
                            <CardHeader className="space-y-2">
                                <Eyebrow>FEAT.03 — teams & rosters</Eyebrow>
                                <CardTitle className="text-lg">Build squads, manage rosters</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-muted-foreground text-sm">
                                    Build squads for inter-club competition and manage who’s on each roster. Add and drop players as the season moves;
                                    the lineup is always current and shared.
                                </p>
                                <div className="divide-border divide-y">
                                    {ROSTER.map((p) => (
                                        <div key={p.seed} className="flex items-center gap-3 py-2.5 text-sm">
                                            <span className="text-display text-muted-foreground w-6">{p.seed}</span>
                                            <span className="flex-1">{p.name}</span>
                                            {p.tag ? <Badge variant="outline">{p.tag}</Badge> : null}
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                        {/* Members & roles */}
                        <Card>
                            <CardHeader className="space-y-2">
                                <Eyebrow>FEAT.04 — members & roles</Eyebrow>
                                <CardTitle className="text-lg">A directory with club-scoped roles</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-muted-foreground text-sm">
                                    A real directory with email invitations and club-scoped roles. Owners run everything, coaches book and run
                                    tournaments and teams, members book. Permissions are granular and scoped to your club only.
                                </p>
                                <div className="overflow-x-auto">
                                    <table className="w-full border-collapse text-left text-xs">
                                        <thead>
                                            <tr className="border-border text-muted-foreground border-b">
                                                <th className="py-2 pr-3 font-medium">role</th>
                                                {PERMISSIONS.map((p) => (
                                                    <th key={p} className="px-1.5 py-2 text-center font-normal whitespace-nowrap">
                                                        {p}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {ROLE_MATRIX.map((row) => (
                                                <tr key={row.role} className="border-border border-b last:border-0">
                                                    <td className="py-2 pr-3 font-medium whitespace-nowrap">{row.role}</td>
                                                    {PERMISSIONS.map((p) => (
                                                        <td key={p} className="px-1.5 py-2 text-center">
                                                            {row.grants.includes(p) ? (
                                                                <Check className="text-foreground mx-auto size-4" role="img" aria-label="granted" />
                                                            ) : (
                                                                <Minus
                                                                    className="text-muted-foreground mx-auto size-4"
                                                                    role="img"
                                                                    aria-label="not granted"
                                                                />
                                                            )}
                                                        </td>
                                                    ))}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                            <CardFooter className="flex-col items-stretch gap-2">
                                <Label htmlFor="invite">Invite a member</Label>
                                <div className="flex gap-2">
                                    <Input id="invite" type="email" placeholder="coach@yourclub.com" readOnly aria-describedby="invite-hint" />
                                    <Button type="button">Send invite</Button>
                                </div>
                                <p id="invite-hint" className="text-muted-foreground text-xs">
                                    They get an email and join your club instantly.
                                </p>
                            </CardFooter>
                        </Card>
                    </div>
                </Section>

                {/* FEAT.05 — Branded subdomains & isolation */}
                <Section id="isolation" label={<>FEAT.05 — branded subdomains & isolation</>}>
                    <div className="grid items-center gap-12 md:grid-cols-2">
                        <div className="space-y-5">
                            <h2 className="text-3xl font-semibold tracking-tight">Every club gets its own branded home — and its own walls.</h2>
                            <p className="text-muted-foreground">
                                Your members go to yourclub.opentennis. Your courts, bookings, tournaments and roster live there and nowhere else,
                                isolated from every other club at the database row level. Running a federation? Stand up a hundred clubs, each fully
                                separated, from one platform.
                            </p>
                            <p className="text-sm">
                                <span className="font-medium">Row-level tenancy.</span>{' '}
                                <span className="text-muted-foreground">No club ever sees another club’s data.</span>
                            </p>
                        </div>
                        <div className="space-y-3">
                            {SUBDOMAINS.map((d) => {
                                const sub = d.split('.')[0];
                                const rest = d.slice(d.indexOf('.'));
                                return (
                                    <div key={d} className="space-y-1">
                                        <div className="border-border bg-card flex items-center gap-2 rounded-full border px-4 py-2 text-sm">
                                            <Lock className="text-muted-foreground size-3" />
                                            <span className="font-mono">
                                                <span className="bg-accent rounded px-1">{sub}</span>
                                                {rest}
                                            </span>
                                        </div>
                                        <p className="text-muted-foreground pl-4 text-xs">isolated · own members, courts, data</p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </Section>

                {/* FIG.02 — Comparison */}
                <Section id="compare" label="FIG.02 — honest comparison">
                    <div className="mx-auto max-w-4xl">
                        <div className="max-w-prose space-y-3">
                            <h2 className="text-3xl font-semibold tracking-tight">We’re not better at everything. We’re better at this.</h2>
                        </div>
                        <div className="mt-8 overflow-x-auto">
                            <table className="w-full border-collapse text-sm">
                                <thead>
                                    <tr className="border-border border-b text-left">
                                        <th className="text-muted-foreground py-3 pr-4 font-medium" />
                                        <th className="text-muted-foreground py-3 pr-4 font-medium">Spreadsheet + group chat</th>
                                        <th className="bg-muted px-4 py-3 font-medium">
                                            <span className="inline-flex items-center gap-2">
                                                OpenTennis <Badge variant="outline">this</Badge>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {COMPARE.map((row, i) => (
                                        <tr key={row.cap} className="border-border border-b">
                                            <td className="py-3 pr-4 font-medium whitespace-nowrap">{row.cap}</td>
                                            <td className="text-muted-foreground py-3 pr-4">− {row.before}</td>
                                            <td className={`px-4 py-3 ${i % 2 === 0 ? 'bg-muted/40' : ''}`}>
                                                <span className="inline-flex items-center gap-2">
                                                    <Check className="size-4 shrink-0" /> {row.after}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <p className="text-muted-foreground mt-6 text-sm">
                            Already on a spreadsheet? Good — you’ve done the hard part of writing down your courts and members. Bringing them over
                            takes minutes.
                        </p>
                    </div>
                </Section>

                {/* FIG.03 — Stats band */}
                <section className="border-border bg-muted border-t">
                    <div className="mx-auto max-w-6xl px-6 py-20">
                        <Eyebrow>FIG.03 — by the numbers</Eyebrow>
                        <div className="mt-8 grid grid-cols-2 gap-y-8 md:grid-cols-4">
                            {STATS.map((s, i) => (
                                <div key={s.t} className={`px-2 md:px-6 ${i > 0 ? 'md:border-border md:border-l' : ''}`}>
                                    <div className="text-display text-5xl leading-none md:text-6xl">{s.n}</div>
                                    <div className="mt-3 text-sm font-medium">{s.t}</div>
                                    <div className="text-muted-foreground text-xs">{s.s}</div>
                                </div>
                            ))}
                        </div>
                        <p className="text-muted-foreground mt-8 text-xs">
                            These are properties of how OpenTennis is built — not marketing averages.
                        </p>
                    </div>
                </section>

                {/* FEAT.06 — Built right */}
                <Section id="platform" label={<>FEAT.06 — for operators & engineers</>}>
                    <div className="grid items-start gap-12 md:grid-cols-2">
                        <div className="space-y-5">
                            <h2 className="text-3xl font-semibold tracking-tight">Built like infrastructure, because it is.</h2>
                            <p className="text-muted-foreground">
                                Running a federation or a chain of clubs? Suspend or reactivate any club from one place, and securely impersonate a
                                club owner to help them without asking for their password. Every club stays isolated; you keep the overview.
                            </p>
                            <div className="divide-border divide-y">
                                <SpecRow label="Suspend / reactivate" value="any club, instantly" />
                                <SpecRow label="Support" value="secure owner impersonation" />
                                <SpecRow label="Isolation" value="enforced at the database row" />
                            </div>
                            <div className="flex flex-wrap gap-2 pt-2">
                                {BUILD_CHIPS.map((c) => (
                                    <Badge key={c} variant="outline">
                                        {c}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle className="text-base">Clubs</CardTitle>
                                <span className="text-muted-foreground text-xs">
                                    <span className="text-display">3</span> active
                                </span>
                            </CardHeader>
                            <CardContent className="divide-border divide-y">
                                {CONSOLE.map((club) => (
                                    <div key={club.name} className="flex items-center justify-between py-3 text-sm">
                                        <span className="inline-flex items-center gap-2">
                                            <ShieldCheck className="text-muted-foreground size-4" /> {club.name}
                                        </span>
                                        <span className="flex items-center gap-3">
                                            <Badge variant={club.status === 'active' ? 'outline' : 'secondary'}>{club.status}</Badge>
                                            <Button variant="ghost" size="sm">
                                                {club.action}
                                            </Button>
                                        </span>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                    <p className="text-muted-foreground mt-6 text-sm">
                        It’s self-documenting and boring on purpose. Boring software is software that’s still running next season.
                    </p>
                </Section>

                {/* FIG.04 — FAQ */}
                <Section id="faq" label="FIG.04 — questions">
                    <div className="mx-auto max-w-3xl">
                        <h2 className="text-3xl font-semibold tracking-tight">The honest answers.</h2>
                        <div className="divide-border border-border mt-8 divide-y border-y">
                            {FAQ.map((item) => (
                                <details key={item.q} className="group py-4">
                                    <summary className="focus-visible:ring-ring flex cursor-pointer list-none items-center justify-between gap-4 rounded-sm text-sm font-medium focus-visible:ring-2 focus-visible:outline-none">
                                        {item.q}
                                        <ChevronDown
                                            className="text-muted-foreground size-4 shrink-0 transition-transform group-open:rotate-180"
                                            aria-hidden="true"
                                        />
                                    </summary>
                                    <p className="text-muted-foreground mt-3 max-w-prose text-sm">{item.a}</p>
                                </details>
                            ))}
                        </div>
                    </div>
                </Section>

                {/* CTA band — the one inverted true-black moment */}
                <section className="bg-foreground text-background">
                    <div className="mx-auto max-w-4xl px-6 py-24 text-center">
                        <p className="text-display text-sm">OPEN·TENNIS</p>
                        <h2 className="mt-4 text-3xl font-semibold tracking-tight md:text-4xl">Stop running your club out of a group chat.</h2>
                        <p className="text-background/70 mx-auto mt-4 max-w-prose">
                            Give your members one place to book, register and play — and give yourself the evening back. Start your club in minutes.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                            <Button size="lg" asChild className="bg-background text-foreground hover:bg-background/80">
                                <Link href={route('register-club.create')}>Start your club</Link>
                            </Button>
                            <Button size="lg" variant="link" asChild className="text-background">
                                <Link href={route('login')}>Sign in</Link>
                            </Button>
                        </div>
                        <div className="text-background/70 mt-6 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-xs">
                            <span className="inline-flex items-center gap-1.5">
                                <span className="bg-background size-1.5 rounded-full" /> no credit card to start
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <span className="bg-background size-1.5 rounded-full" /> your data, isolated
                            </span>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-border border-t">
                    <div className="mx-auto max-w-6xl px-6 py-16">
                        <div className="grid grid-cols-2 gap-8 text-sm md:grid-cols-4">
                            <div className="col-span-2 space-y-3 md:col-span-1">
                                <div className="text-display text-lg">OPEN·TENNIS</div>
                                <p className="text-muted-foreground max-w-xs text-xs">
                                    The club operating system. Courts, tournaments, teams and members on one isolated subdomain.
                                </p>
                                <ThemeToggle />
                            </div>
                            <FooterCol
                                title="Product"
                                links={[
                                    { label: 'Court booking', href: '#booking' },
                                    { label: 'Tournaments', href: '#tournaments' },
                                    { label: 'Teams & rosters', href: '#teams-members' },
                                    { label: 'Members & roles', href: '#teams-members' },
                                ]}
                            />
                            <FooterCol
                                title="Company"
                                links={[
                                    { label: 'How it works', href: '#how' },
                                    { label: 'Compare', href: '#compare' },
                                    { label: 'Design system', routeName: 'ui.gallery' },
                                ]}
                            />
                            <FooterCol
                                title="Get started"
                                links={[
                                    { label: 'Start your club', routeName: 'register-club.create' },
                                    { label: 'Sign in', routeName: 'login' },
                                    { label: 'Personal sign-up', routeName: 'register' },
                                ]}
                            />
                        </div>
                        <Separator className="my-8" />
                        <div className="text-muted-foreground flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span>OpenTennis • 2026 — run your club like software, not spreadsheets.</span>
                            <StatusDot label="all systems operational" />
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}

type FooterLink = { label: string; href?: string; routeName?: string };

function FooterCol({ title, links }: { title: string; links: FooterLink[] }) {
    return (
        <div className="space-y-3">
            <p className="text-muted-foreground text-xs font-medium tracking-[0.15em] uppercase">{title}</p>
            <ul className="space-y-2">
                {links.map((l) => (
                    <li key={l.label}>
                        {l.routeName ? (
                            <Link href={route(l.routeName)} className="text-muted-foreground hover:text-foreground transition-colors">
                                {l.label}
                            </Link>
                        ) : (
                            <a href={l.href} className="text-muted-foreground hover:text-foreground transition-colors">
                                {l.label}
                            </a>
                        )}
                    </li>
                ))}
            </ul>
        </div>
    );
}
