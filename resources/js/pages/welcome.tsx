import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarClock, Check, ChevronDown, Lock, Minus, Monitor, Moon, Sun } from 'lucide-react';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { type Appearance, useAppearance } from '@/hooks/use-appearance';

/*
 * OpenTennis home page — rendered at route('home') on the central domain.
 * Voice: warm and human, written for the people in a community who just want to
 * get on court — not for operators or engineers. Monochrome, JetBrains Mono, with
 * the Doto dot-matrix face (.text-display) used only for numerals and the wordmark.
 * Every feature is shown with a small, real-looking mock built from divs + tokens.
 */

// ── Reused pieces ────────────────────────────────────────────────────────────

/** The light/dark/system theme toggle, as sun / moon / screen icons. */
const THEME_MODES: { value: Appearance; label: string; Icon: typeof Sun }[] = [
    { value: 'light', label: 'Light', Icon: Sun },
    { value: 'dark', label: 'Dark', Icon: Moon },
    { value: 'system', label: 'System', Icon: Monitor },
];

function ThemeToggle() {
    const { appearance, updateAppearance } = useAppearance();
    return (
        <div className="border-border flex items-center gap-1 rounded-md border p-0.5" role="group" aria-label="Theme">
            {THEME_MODES.map(({ value, label, Icon }) => (
                <button
                    key={value}
                    type="button"
                    aria-label={label}
                    aria-pressed={appearance === value}
                    title={label}
                    onClick={() => updateAppearance(value)}
                    className={`focus-visible:ring-ring rounded p-1.5 transition-colors focus-visible:ring-2 focus-visible:outline-none ${
                        appearance === value ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'
                    }`}
                >
                    <Icon className="size-4" aria-hidden="true" />
                </button>
            ))}
        </div>
    );
}

/** A small uppercase section eyebrow. */
function Eyebrow({ children }: { children: React.ReactNode }) {
    return <p className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">{children}</p>;
}

/** A tiny status indicator: filled dot + optional lowercase label. */
function StatusDot({ label }: { label?: string }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            <span className="bg-foreground size-1.5 rounded-full" />
            {label ? <span className="text-muted-foreground text-[11px]">{label}</span> : null}
        </span>
    );
}

/** A label → value row (muted label left, value right). */
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
                <span className="text-muted-foreground text-[11px] tracking-[0.2em] uppercase">Centre Court · Tuesday</span>
                <StatusDot label="live" />
            </div>
            <div
                className="grid grid-cols-[auto_repeat(5,1fr)] gap-1.5"
                role="img"
                aria-label="A court board: filled cells are already booked, light cells are free, the striped cell is your booking, and the red-outlined cell is closed for upkeep."
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
                    <span className="bg-muted size-3 rounded-sm" /> free
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="bg-foreground size-3 rounded-sm" /> taken
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className={`border-border size-3 rounded-sm border ${STRIPE}`} /> yours
                </span>
                <span className="text-foreground ml-auto font-medium">+ never double-booked</span>
            </div>
        </div>
    );
}

/** The dot-matrix scoreboard strip, reused on the play section. */
function Scoreboard() {
    return (
        <Card>
            <CardContent className="flex flex-wrap items-end justify-between gap-8 pt-6">
                <div>
                    <div className="text-muted-foreground text-xs">Saturday final · Centre Court</div>
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
    { href: '#booking', label: 'Book a court' },
    { href: '#play', label: 'Play' },
    { href: '#community', label: 'Your club' },
    { href: '#faq', label: 'FAQ' },
];

const BOOKING_CHECKS = [
    'See every court and free slot at a glance',
    'Book from your phone in seconds',
    'Your court is yours — guaranteed, every time',
    'Cancel in a tap and free it up for a neighbour',
    'Quiet hours and members-only times, if you want them',
];

const PLAY_CATEGORIES = [
    { name: 'Men’s Singles', entrants: '24' },
    { name: 'Women’s Singles', entrants: '16' },
    { name: 'Mixed Doubles', entrants: '12' },
];

// Friendly view of the club roles (mirrors RolePermissionSeeder::roleMatrix()):
// Organiser = club-admin (everything); Coach = book + run events + make teams; Member = book.
const ABILITIES = ['Run the club', 'Invite people', 'Look after courts', 'Book a court', 'Sort out bookings', 'Run events', 'Make teams'];
const ROLE_MATRIX: { role: string; can: string[] }[] = [
    { role: 'Organiser', can: [...ABILITIES] },
    { role: 'Coach', can: ['Book a court', 'Run events', 'Make teams'] },
    { role: 'Member', can: ['Book a court'] },
];

const ROSTER = [
    { seed: '01', name: 'Aisha P.', tag: 'captain' },
    { seed: '02', name: 'Jordan O.', tag: '' },
    { seed: '03', name: 'Mara L.', tag: '' },
    { seed: '04', name: 'Ravi H.', tag: '' },
];

const SUBDOMAINS = ['oakwood-tennis.opentennis', 'baseline-club.opentennis', 'riverside-courts.opentennis'];

const COMPARE: { thing: string; before: string; after: string }[] = [
    { thing: 'Finding a free court', before: 'text the group chat and hope', after: 'see what’s open, right now' },
    { thing: 'Booking a slot', before: 'ask whoever has the sheet', after: 'tap once — it’s yours' },
    { thing: 'Double-bookings', before: 'two families, one court, awkward', after: 'simply can’t happen' },
    { thing: 'Club socials & leagues', before: 'flyers and reply-all emails', after: 'sign up in a tap' },
    { thing: 'Finding someone to play', before: 'who’s around this weekend?', after: 'see who’s on and join in' },
    { thing: 'A change of plan', before: 'did everyone get the message?', after: 'everyone sees it instantly' },
];

const STATS = [
    { n: '30s', t: 'to book a court', s: 'from your phone, anywhere' },
    { n: '0', t: 'double-bookings', s: 'the court is yours, full stop' },
    { n: '24/7', t: 'open for booking', s: 'reserve at midnight if you like' },
    { n: '1 tap', t: 'to join a game', s: 'see who’s playing and jump in' },
];

const HOUSE_RULES = ['Book up to 7 days ahead', '1 hour per slot at peak times', 'Members only at weekends', 'Guests welcome off-peak'];

const FAQ = [
    {
        q: 'Do my neighbours need to download an app?',
        a: 'Nope. Everyone just opens your club’s web page on their phone or laptop and books — no app store, no installs, nothing to set up.',
    },
    {
        q: 'Can two people end up with the same court?',
        a: 'Never. The moment someone books a slot it’s theirs — the next person simply sees it’s taken and picks another time. No more awkward run-ins on a Saturday morning.',
    },
    {
        q: 'Is it only for tennis?',
        a: 'Courts are courts — tennis, padel, badminton, whatever your community plays. Add your courts and you’re away.',
    },
    {
        q: 'What if I book and then can’t make it?',
        a: 'Cancel in a tap and your slot opens straight back up for a neighbour. No wasted court time, no guilty messages.',
    },
    {
        q: 'Who can see our club’s schedule?',
        a: 'Only your members. Your club gets its own private page — it’s your community’s space, not a public listing anyone can browse.',
    },
    {
        q: 'Who looks after it day to day?',
        a: 'Whoever organises your club. They set a few simple house rules — how far ahead people can book, peak-hour limits, guests — and everything else just looks after itself.',
    },
];

/** A full-width section with a top hairline + eyebrow. */
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
            <Head title="OpenTennis — book a court, round up the neighbours, play" />

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
                                        <Link href={route('register-club.create')}>Get your club started</Link>
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
                            <Eyebrow>For neighbourhoods, clubs & communities</Eyebrow>
                            <h1 className="text-4xl leading-[1.05] font-semibold tracking-tight md:text-5xl">
                                Book a court. Round up the neighbours. Play.
                            </h1>
                            <p className="text-muted-foreground max-w-prose text-base md:text-lg">
                                OpenTennis gives your community one easy place to reserve courts, join games and socials, and keep everyone in the
                                loop — so getting on court is the simple part of your day.
                            </p>
                            <div className="flex flex-wrap gap-3">
                                <Button size="lg" asChild>
                                    <Link href={route('register-club.create')}>Get your club started</Link>
                                </Button>
                                <Button size="lg" variant="outline" asChild>
                                    <a href="#how">See how it works</a>
                                </Button>
                            </div>
                            <div className="text-muted-foreground flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                                <StatusDot label="Books in seconds" />
                                <StatusDot label="Works on any phone" />
                                <StatusDot label="No more double-bookings" />
                            </div>
                        </div>
                        <CourtBoard />
                    </div>
                </section>

                {/* How it works */}
                <Section id="how" label="How it works">
                    <div className="max-w-prose space-y-3">
                        <h2 className="text-3xl font-semibold tracking-tight">One place your whole community shares.</h2>
                        <p className="text-muted-foreground">
                            No more “is court two free?” in the group chat. Everyone sees the same up-to-date schedule, books the slot they want, and
                            turns up knowing it’s theirs — your club, all in one spot your neighbours actually enjoy using.
                        </p>
                    </div>
                    <div className="mt-10 grid items-center gap-8 md:grid-cols-[1fr_auto_1fr]">
                        {/* BEFORE */}
                        <div className="grid grid-cols-2 gap-3">
                            {['The group chat', 'A sheet on the wall', 'Texts to whoever has the keys', 'Sticky notes'].map((n) => (
                                <div key={n} className="border-border bg-muted text-muted-foreground rounded-md border px-3 py-4 text-center text-xs">
                                    {n}
                                </div>
                            ))}
                        </div>
                        {/* arrow */}
                        <div className="text-muted-foreground flex flex-col items-center gap-2">
                            <span className="text-display text-2xl">→</span>
                            <span className="text-[11px] tracking-[0.15em] uppercase">one easy place</span>
                        </div>
                        {/* AFTER */}
                        <div className="bg-foreground text-background rounded-lg p-5">
                            <div className="mb-3 flex items-center gap-2 text-sm font-medium">
                                <Lock className="size-3.5" /> yourclub.opentennis
                            </div>
                            <div className="space-y-2 text-sm">
                                {['Courts', 'Events', 'Teams', 'Members'].map((row) => (
                                    <div key={row} className="border-background/20 flex items-center justify-between border-t pt-2">
                                        <span>{row}</span>
                                        <span className="bg-background size-1.5 rounded-full" />
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                    <p className="mt-6 text-sm">
                        <span className="text-muted-foreground line-through">− a dozen messages to find a court</span>{' '}
                        <span className="ml-3 font-medium">+ one tap and you’re booked</span>
                    </p>
                </Section>

                {/* Book a court */}
                <Section id="booking" label="Book a court">
                    <div className="grid items-center gap-12 md:grid-cols-2">
                        <div className="space-y-5">
                            <h2 className="text-3xl font-semibold tracking-tight">See what’s free. Tap. You’re on.</h2>
                            <p className="text-muted-foreground">
                                Open your club’s page, glance at the courts, and grab the slot you want — from the sofa, the office, or the car park.
                                Plans change? Cancel in a tap and a neighbour gets your spot.
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
                                    <Button className="w-full">Book it</Button>
                                </CardFooter>
                            </Card>
                            <Alert>
                                <CalendarClock className="size-4" />
                                <AlertTitle>Just taken</AlertTitle>
                                <AlertDescription>Someone grabbed 14:00 a moment ago — pick another time and you’re set.</AlertDescription>
                            </Alert>
                        </div>
                    </div>
                </Section>

                {/* Play & compete */}
                <Section id="play" label="Play & compete">
                    <div className="grid items-center gap-12 md:grid-cols-2">
                        <div className="order-2 space-y-4 md:order-1">
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between">
                                    <CardTitle className="text-base">Spring Social 2026</CardTitle>
                                    <Badge>sign-ups open</Badge>
                                </CardHeader>
                                <CardContent className="divide-border divide-y">
                                    {PLAY_CATEGORIES.map((cat) => (
                                        <div key={cat.name} className="flex items-center justify-between py-2.5 text-sm">
                                            <span className="inline-flex items-center gap-2">
                                                <StatusDot /> {cat.name}
                                            </span>
                                            <span className="text-display text-lg">{cat.entrants}</span>
                                        </div>
                                    ))}
                                    <div className="space-y-1.5 pt-3">
                                        <div className="text-muted-foreground flex items-center justify-between text-xs">
                                            <span>signed up</span>
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
                                Ladders, leagues and the Saturday social — without the spreadsheet.
                            </h2>
                            <p className="text-muted-foreground">
                                Your club sets up friendly tournaments and events; you sign up with a tap and see who else is in. From the weekend
                                round-robin to the club championship, it’s all in one place — and nobody’s copying names off a clipboard.
                            </p>
                            <div className="divide-border divide-y">
                                <SpecRow label="Events" value="as many as you like" />
                                <SpecRow label="Signing up" value="one tap" />
                                <SpecRow label="Who’s playing" value="see the whole list" />
                                <SpecRow label="Changed your mind?" value="drop out anytime" />
                            </div>
                        </div>
                    </div>
                </Section>

                {/* Your people */}
                <Section id="community" label="Your people">
                    <div className="max-w-prose space-y-3">
                        <h2 className="text-3xl font-semibold tracking-tight">It’s the people that make a club.</h2>
                    </div>
                    <div className="mt-8 grid gap-6 md:grid-cols-2">
                        {/* Teams */}
                        <Card>
                            <CardHeader className="space-y-2">
                                <Eyebrow>Teams</Eyebrow>
                                <CardTitle className="text-lg">Get a team together</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-muted-foreground text-sm">
                                    Pull together a squad for the league or a friendly against the next neighbourhood, and keep track of who’s playing
                                    each week — all in one tidy list.
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
                        {/* Roles */}
                        <Card>
                            <CardHeader className="space-y-2">
                                <Eyebrow>Members & roles</Eyebrow>
                                <CardTitle className="text-lg">Everyone gets the right access</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-muted-foreground text-sm">
                                    Organisers run the club, coaches sort out teams and events, and members book and play. No fuss, and nobody
                                    stepping on anyone’s toes.
                                </p>
                                <div className="overflow-x-auto">
                                    <table className="w-full border-collapse text-left text-xs">
                                        <thead>
                                            <tr className="border-border text-muted-foreground border-b">
                                                <th className="py-2 pr-3 font-medium">who</th>
                                                {ABILITIES.map((a) => (
                                                    <th key={a} className="px-1.5 py-2 text-center font-normal whitespace-nowrap">
                                                        {a}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {ROLE_MATRIX.map((row) => (
                                                <tr key={row.role} className="border-border border-b last:border-0">
                                                    <td className="py-2 pr-3 font-medium whitespace-nowrap">{row.role}</td>
                                                    {ABILITIES.map((a) => (
                                                        <td key={a} className="px-1.5 py-2 text-center">
                                                            {row.can.includes(a) ? (
                                                                <Check className="text-foreground mx-auto size-4" role="img" aria-label="yes" />
                                                            ) : (
                                                                <Minus className="text-muted-foreground mx-auto size-4" role="img" aria-label="no" />
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
                                <Label htmlFor="invite">Invite a neighbour</Label>
                                <div className="flex gap-2">
                                    <Input id="invite" type="email" placeholder="neighbour@email.com" readOnly aria-describedby="invite-hint" />
                                    <Button type="button">Send invite</Button>
                                </div>
                                <p id="invite-hint" className="text-muted-foreground text-xs">
                                    They get a friendly email and they’re in.
                                </p>
                            </CardFooter>
                        </Card>
                    </div>
                </Section>

                {/* Your club online */}
                <Section id="space" label="Your club online">
                    <div className="grid items-center gap-12 md:grid-cols-2">
                        <div className="space-y-5">
                            <h2 className="text-3xl font-semibold tracking-tight">Your club gets its own little corner of the web.</h2>
                            <p className="text-muted-foreground">
                                Your neighbours just go to yourclub.opentennis — see what’s on, book a court, join in. It’s your community’s own
                                space, not a noisy public app, and nobody outside your club can peek in.
                            </p>
                            <p className="text-sm">
                                <span className="font-medium">Private to your members.</span>{' '}
                                <span className="text-muted-foreground">Your courts, your events, your people.</span>
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
                                        <p className="text-muted-foreground pl-4 text-xs">one club · its own private home</p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </Section>

                {/* The old way vs the new */}
                <Section id="compare" label="The old way vs the OpenTennis way">
                    <div className="mx-auto max-w-4xl">
                        <div className="max-w-prose space-y-3">
                            <h2 className="text-3xl font-semibold tracking-tight">Less faff. More tennis.</h2>
                        </div>
                        <div className="mt-8 overflow-x-auto">
                            <table className="w-full border-collapse text-sm">
                                <thead>
                                    <tr className="border-border border-b text-left">
                                        <th className="text-muted-foreground py-3 pr-4 font-medium" />
                                        <th className="text-muted-foreground py-3 pr-4 font-medium">Group chat & a sheet on the wall</th>
                                        <th className="bg-muted px-4 py-3 font-medium">
                                            <span className="inline-flex items-center gap-2">
                                                OpenTennis <Badge variant="outline">this</Badge>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {COMPARE.map((row, i) => (
                                        <tr key={row.thing} className="border-border border-b">
                                            <td className="py-3 pr-4 font-medium whitespace-nowrap">{row.thing}</td>
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
                            Already keep a booking sheet? Lovely — you’ve done the hard part. Pop your courts and members in and you’re playing in
                            minutes.
                        </p>
                    </div>
                </Section>

                {/* Why neighbours love it */}
                <section className="border-border bg-muted border-t">
                    <div className="mx-auto max-w-6xl px-6 py-20">
                        <Eyebrow>Why neighbours love it</Eyebrow>
                        <div className="mt-8 grid grid-cols-2 gap-y-8 md:grid-cols-4">
                            {STATS.map((s, i) => (
                                <div key={s.t} className={`px-2 md:px-6 ${i > 0 ? 'md:border-border md:border-l' : ''}`}>
                                    <div className="text-display text-5xl leading-none md:text-6xl">{s.n}</div>
                                    <div className="mt-3 text-sm font-medium">{s.t}</div>
                                    <div className="text-muted-foreground text-xs">{s.s}</div>
                                </div>
                            ))}
                        </div>
                        <p className="text-muted-foreground mt-8 text-xs">The little things that make getting on court feel easy.</p>
                    </div>
                </section>

                {/* For whoever runs it */}
                <Section id="organise" label="For whoever runs it">
                    <div className="grid items-start gap-12 md:grid-cols-2">
                        <div className="space-y-5">
                            <h2 className="text-3xl font-semibold tracking-tight">Easy to run. Fair for everyone.</h2>
                            <p className="text-muted-foreground">
                                Whoever looks after your club sets a few simple house rules — how far ahead people can book, peak-hour limits,
                                members-only times — and OpenTennis keeps everything fair on its own. No favourites, no chasing, no spreadsheet on a
                                Sunday night.
                            </p>
                            <div className="divide-border divide-y">
                                <SpecRow label="Setting it up" value="an afternoon, not a project" />
                                <SpecRow label="Keeping it fair" value="handled automatically" />
                                <SpecRow label="Helping a member" value="a couple of taps" />
                            </div>
                        </div>
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Your club’s house rules</CardTitle>
                            </CardHeader>
                            <CardContent className="divide-border divide-y">
                                {HOUSE_RULES.map((rule) => (
                                    <div key={rule} className="flex items-center gap-3 py-3 text-sm">
                                        <Check className="text-foreground size-4 shrink-0" />
                                        <span>{rule}</span>
                                    </div>
                                ))}
                                <p className="text-muted-foreground pt-3 text-xs">Set once — they apply to everyone, automatically.</p>
                            </CardContent>
                        </Card>
                    </div>
                </Section>

                {/* FAQ */}
                <Section id="faq" label="Good questions">
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

                {/* CTA band */}
                <section className="bg-foreground text-background">
                    <div className="mx-auto max-w-4xl px-6 py-24 text-center">
                        <p className="text-display text-sm">OPEN·TENNIS</p>
                        <h2 className="mt-4 text-3xl font-semibold tracking-tight md:text-4xl">Bring your community’s courts to life.</h2>
                        <p className="text-background/70 mx-auto mt-4 max-w-prose">
                            Set your club up in minutes and give your neighbours the easiest way yet to book, play and get together.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                            <Button size="lg" asChild className="bg-background text-foreground hover:bg-background/80">
                                <Link href={route('register-club.create')}>Get your club started</Link>
                            </Button>
                            <Button size="lg" variant="link" asChild className="text-background">
                                <Link href={route('login')}>Sign in</Link>
                            </Button>
                        </div>
                        <div className="text-background/70 mt-6 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-xs">
                            <span className="inline-flex items-center gap-1.5">
                                <span className="bg-background size-1.5 rounded-full" /> no app to download
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <span className="bg-background size-1.5 rounded-full" /> up and running in minutes
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
                                    The easiest way for your community to book courts, play and get together.
                                </p>
                                <ThemeToggle />
                            </div>
                            <FooterCol
                                title="Playing"
                                links={[
                                    { label: 'Book a court', href: '#booking' },
                                    { label: 'Events & leagues', href: '#play' },
                                    { label: 'Teams', href: '#community' },
                                    { label: 'Members', href: '#community' },
                                ]}
                            />
                            <FooterCol
                                title="About"
                                links={[
                                    { label: 'How it works', href: '#how' },
                                    { label: 'The old way vs us', href: '#compare' },
                                    { label: 'Design system', routeName: 'ui.gallery' },
                                ]}
                            />
                            <FooterCol
                                title="Get started"
                                links={[
                                    { label: 'Get your club started', routeName: 'register-club.create' },
                                    { label: 'Sign in', routeName: 'login' },
                                    { label: 'Join your club', routeName: 'register' },
                                ]}
                            />
                        </div>
                        <Separator className="my-8" />
                        <div className="text-muted-foreground flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span>OpenTennis • 2026 — courts, games and good company, sorted.</span>
                            <span>
                                Crafted by <span className="text-foreground font-medium">GH Global Systems</span>
                            </span>
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
