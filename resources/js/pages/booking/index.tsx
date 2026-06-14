import { router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import ClubLayout from '@/layouts/club-layout';

// 0 = Monday .. 6 = Sunday — matches the day_of_week stored on court_availability.
const DAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

// Slots are generated on the hour. A slot is bookable when [start, end) lies inside an
// availability window for that weekday and overlaps no blackout and no reserved booking.
const SLOT_MINUTES = 60;

interface AvailabilityWindow {
    day_of_week: number;
    opens_at: string | null; // "HH:MM"
    closes_at: string | null; // "HH:MM"
}

interface Court {
    id: number;
    name: string;
    surface: string | null;
    availability: AvailabilityWindow[];
    blackouts: { starts_at: string | null; ends_at: string | null }[];
}

interface Interval {
    id?: number;
    court_id: number;
    starts_at: string | null; // ISO
    ends_at: string | null; // ISO
}

interface MyBooking {
    id: number;
    court_id: number;
    court_name: string | null;
    starts_at: string | null;
    ends_at: string | null;
    status: string;
    can_cancel: boolean;
}

interface BookingIndexProps {
    courts: Court[];
    courtBookings: Interval[];
    myBookings: MyBooking[];
}

/** Carbon day_of_week (Sun=0..Sat=6) remapped to our domain (Mon=0..Sun=6). */
function domainDow(date: Date): number {
    return (date.getDay() + 6) % 7;
}

/** Format an ISO datetime as a short local "Wed 17 Jun, 18:00" label. */
function fmt(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function timeOnly(iso: string | null): string {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

interface Slot {
    startsAt: Date;
    endsAt: Date;
    label: string;
    available: boolean;
}

/** A local Date for the given YYYY-MM-DD at HH:00. */
function dateAtHour(dateStr: string, hour: number): Date {
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d, hour, 0, 0, 0);
}

function overlaps(aStart: Date, aEnd: Date, bStart: Date, bEnd: Date): boolean {
    // Half-open [start, end): touching endpoints do NOT overlap.
    return aStart < bEnd && bStart < aEnd;
}

function buildSlots(court: Court | undefined, dateStr: string, taken: Interval[]): Slot[] {
    if (!court || !dateStr) return [];

    const dow = domainDow(dateAtHour(dateStr, 0));
    const windows = court.availability.filter((w) => w.day_of_week === dow && w.opens_at && w.closes_at);
    if (windows.length === 0) return [];

    const blackouts = court.blackouts
        .filter((b) => b.starts_at && b.ends_at)
        .map((b) => ({ start: new Date(b.starts_at as string), end: new Date(b.ends_at as string) }));

    const bookings = taken
        .filter((b) => b.court_id === court.id && b.starts_at && b.ends_at)
        .map((b) => ({ start: new Date(b.starts_at as string), end: new Date(b.ends_at as string) }));

    const now = new Date();
    const slots: Slot[] = [];

    for (const w of windows) {
        const openHour = Number((w.opens_at as string).slice(0, 2));
        const closeHour = Number((w.closes_at as string).slice(0, 2));

        for (let hour = openHour; hour + SLOT_MINUTES / 60 <= closeHour; hour += SLOT_MINUTES / 60) {
            const startsAt = dateAtHour(dateStr, hour);
            const endsAt = new Date(startsAt.getTime() + SLOT_MINUTES * 60_000);

            const inPast = endsAt <= now;
            const blacked = blackouts.some((b) => overlaps(startsAt, endsAt, b.start, b.end));
            const booked = bookings.some((b) => overlaps(startsAt, endsAt, b.start, b.end));

            slots.push({
                startsAt,
                endsAt,
                label: `${String(hour).padStart(2, '0')}:00`,
                available: !inPast && !blacked && !booked,
            });
        }
    }

    return slots;
}

/** Local Date -> ISO-ish string the backend Carbon::parse accepts ("YYYY-MM-DDTHH:MM:SS"). */
function toLocalIso(d: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
}

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline'> = {
    reserved: 'default',
    completed: 'outline',
    cancelled: 'secondary',
};

export default function BookingIndex({ courts, courtBookings, myBookings }: BookingIndexProps) {
    const page = usePage<{ errors: Record<string, string>; flash?: { status?: string } }>();
    const bookingError = page.props.errors?.booking;
    const flash = page.props.flash?.status;

    const today = useMemo(() => {
        const d = new Date();
        const pad = (n: number) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    }, []);

    const [courtId, setCourtId] = useState<string>(courts[0] ? String(courts[0].id) : '');
    const [date, setDate] = useState<string>(today);
    const [processing, setProcessing] = useState(false);

    const selectedCourt = courts.find((c) => String(c.id) === courtId);
    const slots = useMemo(() => buildSlots(selectedCourt, date, courtBookings), [selectedCourt, date, courtBookings]);

    const book = (slot: Slot) => {
        router.post(
            route('bookings.store'),
            {
                court_id: courtId,
                starts_at: toLocalIso(slot.startsAt),
                ends_at: toLocalIso(slot.endsAt),
            },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const cancel = (b: MyBooking) => {
        if (confirm(`Cancel your booking on ${b.court_name ?? 'this court'} at ${fmt(b.starts_at)}?`)) {
            router.delete(route('bookings.destroy', b.id), { preserveScroll: true });
        }
    };

    return (
        <ClubLayout title="Bookings">
            <div className="space-y-8">
                <header className="space-y-1">
                    <p className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Booking</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Book a court</h1>
                    <p className="text-muted-foreground text-sm">Pick a court and a day to see open slots.</p>
                </header>

                {flash && (
                    <div className="border-border bg-card rounded-lg border px-4 py-2 text-sm" role="status">
                        {flash}
                    </div>
                )}

                {bookingError && (
                    <div className="border-destructive/40 bg-destructive/5 text-destructive rounded-lg border px-4 py-2 text-sm" role="alert">
                        {bookingError}
                    </div>
                )}

                {courts.length === 0 ? (
                    <p className="text-muted-foreground text-sm">No bookable courts yet.</p>
                ) : (
                    <section className="border-border bg-card space-y-4 rounded-xl border p-5">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="booking-court">Court</Label>
                                <Select value={courtId} onValueChange={setCourtId}>
                                    <SelectTrigger id="booking-court">
                                        <SelectValue placeholder="Select a court" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {courts.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.name}
                                                {c.surface ? ` · ${c.surface}` : ''}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="booking-date">Date</Label>
                                <Input id="booking-date" type="date" min={today} value={date} onChange={(e) => setDate(e.target.value)} />
                            </div>
                        </div>

                        <Separator />

                        <div className="space-y-2">
                            <p className="text-muted-foreground text-xs font-medium tracking-wider uppercase">
                                {selectedCourt ? `${selectedCourt.name} · ${DAY_LABELS[domainDow(dateAtHour(date, 0))]}` : 'Slots'}
                            </p>
                            {slots.length === 0 ? (
                                <p className="text-muted-foreground text-sm">This court has no open hours on the selected day.</p>
                            ) : (
                                <div className="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                                    {slots.map((slot) => (
                                        <Button
                                            key={slot.label}
                                            type="button"
                                            variant={slot.available ? 'outline' : 'ghost'}
                                            size="sm"
                                            disabled={!slot.available || processing}
                                            onClick={() => book(slot)}
                                            aria-label={`Book ${slot.label}`}
                                        >
                                            <span className="text-display">{slot.label}</span>
                                        </Button>
                                    ))}
                                </div>
                            )}
                            <p className="text-muted-foreground text-xs">Greyed-out times are taken, blacked out, or in the past.</p>
                        </div>
                    </section>
                )}

                <section className="space-y-3">
                    <h2 className="text-muted-foreground text-sm font-semibold tracking-wider uppercase">My bookings</h2>
                    {myBookings.length === 0 ? (
                        <p className="text-muted-foreground text-sm">You have no bookings yet.</p>
                    ) : (
                        <ul className="space-y-2">
                            {myBookings.map((b) => (
                                <li key={b.id} className="border-border bg-card flex items-center justify-between gap-4 rounded-lg border px-4 py-3">
                                    <div className="flex items-center gap-4">
                                        <span className="text-display text-muted-foreground text-lg">{timeOnly(b.starts_at)}</span>
                                        <div className="space-y-0.5">
                                            <p className="text-sm font-medium">{b.court_name ?? 'Court'}</p>
                                            <p className="text-muted-foreground text-xs">
                                                {fmt(b.starts_at)} – {timeOnly(b.ends_at)}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Badge variant={STATUS_VARIANT[b.status] ?? 'outline'} className="capitalize">
                                            {b.status}
                                        </Badge>
                                        {b.can_cancel && (
                                            <Button variant="ghost" size="sm" onClick={() => cancel(b)}>
                                                Cancel
                                            </Button>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </ClubLayout>
    );
}
