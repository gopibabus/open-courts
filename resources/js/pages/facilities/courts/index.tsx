import { router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import ClubLayout from '@/layouts/club-layout';

// 0 = Monday .. 6 = Sunday — matches the smallInteger stored on court_availability.
const DAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const SURFACES = ['hard', 'clay', 'grass', 'carpet'] as const;

interface Window {
    id: number;
    day_of_week: number;
    opens_at: string | null;
    closes_at: string | null;
}

interface Blackout {
    id: number;
    starts_at: string | null;
    ends_at: string | null;
    reason: string | null;
}

interface Court {
    id: number;
    name: string;
    surface: string | null;
    is_active: boolean;
    availability: Window[];
    blackouts: Blackout[];
}

interface CourtsIndexProps {
    courts: Court[];
    canManage: boolean;
}

interface CourtForm {
    name: string;
    surface: string;
    is_active: boolean;
    [key: string]: string | boolean;
}

function CourtFormDialog({
    title,
    trigger,
    initial,
    submitLabel,
    method,
    action,
}: {
    title: string;
    trigger: React.ReactNode;
    initial: CourtForm;
    submitLabel: string;
    method: 'post' | 'put';
    action: string;
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, put, processing, errors, reset } = useForm<CourtForm>(initial);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const submitter = method === 'post' ? post : put;
        submitter(action, {
            preserveScroll: true,
            onSuccess: () => {
                if (method === 'post') {
                    reset();
                }
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>A court members can book and schedule around.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="court-name">Name</Label>
                        <Input
                            id="court-name"
                            value={data.name}
                            autoFocus
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Centre Court"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="court-surface">Surface</Label>
                        <Select value={data.surface} onValueChange={(v) => setData('surface', v)}>
                            <SelectTrigger id="court-surface">
                                <SelectValue placeholder="Select a surface" />
                            </SelectTrigger>
                            <SelectContent>
                                {SURFACES.map((s) => (
                                    <SelectItem key={s} value={s} className="capitalize">
                                        {s}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.surface} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="accent-foreground size-4"
                        />
                        Active (bookable)
                    </label>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {submitLabel}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

interface AvailabilityWindowRow {
    day_of_week: number;
    opens_at: string;
    closes_at: string;
}

interface AvailabilityFormShape {
    windows: AvailabilityWindowRow[];
    [key: string]: AvailabilityWindowRow[];
}

function AvailabilityEditor({ court }: { court: Court }) {
    const [open, setOpen] = useState(false);
    const { data, setData, put, processing, errors } = useForm<AvailabilityFormShape>({
        windows: court.availability.map((w) => ({
            day_of_week: w.day_of_week,
            opens_at: w.opens_at ?? '09:00',
            closes_at: w.closes_at ?? '21:00',
        })),
    });

    const addWindow = () => setData('windows', [...data.windows, { day_of_week: 0, opens_at: '09:00', closes_at: '21:00' }]);

    const removeWindow = (index: number) =>
        setData(
            'windows',
            data.windows.filter((_, i) => i !== index),
        );

    const updateWindow = (index: number, key: 'day_of_week' | 'opens_at' | 'closes_at', value: string | number) =>
        setData(
            'windows',
            data.windows.map((w, i) => (i === index ? { ...w, [key]: value } : w)),
        );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('courts.availability.update', court.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Edit availability
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-lg">
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Weekly availability — {court.name}</DialogTitle>
                        <DialogDescription>Replace the recurring open windows for this court.</DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2">
                        {data.windows.length === 0 && <p className="text-muted-foreground text-sm">No open windows — this court is closed.</p>}
                        {data.windows.map((w, i) => (
                            <div key={i} className="flex items-center gap-2">
                                <Select value={String(w.day_of_week)} onValueChange={(v) => updateWindow(i, 'day_of_week', Number(v))}>
                                    <SelectTrigger className="w-28">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {DAY_LABELS.map((label, day) => (
                                            <SelectItem key={day} value={String(day)}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Input
                                    type="time"
                                    value={w.opens_at}
                                    onChange={(e) => updateWindow(i, 'opens_at', e.target.value)}
                                    className="w-32"
                                />
                                <span className="text-muted-foreground">—</span>
                                <Input
                                    type="time"
                                    value={w.closes_at}
                                    onChange={(e) => updateWindow(i, 'closes_at', e.target.value)}
                                    className="w-32"
                                />
                                <Button type="button" variant="ghost" size="icon" onClick={() => removeWindow(i)} aria-label="Remove window">
                                    <Trash2 />
                                </Button>
                            </div>
                        ))}
                        <InputError message={errors.windows as string | undefined} />
                    </div>

                    <Button type="button" variant="ghost" size="sm" onClick={addWindow}>
                        <Plus /> Add window
                    </Button>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Save schedule
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

interface BlackoutFormShape {
    court_id: string;
    starts_at: string;
    ends_at: string;
    reason: string;
    [key: string]: string;
}

function BlackoutDialog({ courts }: { courts: Court[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<BlackoutFormShape>({
        court_id: '',
        starts_at: '',
        ends_at: '',
        reason: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('blackouts.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">Add blackout</Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Add a blackout</DialogTitle>
                        <DialogDescription>Block a court (or the whole club) for a one-off period.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="blackout-court">Court</Label>
                        <Select value={data.court_id} onValueChange={(v) => setData('court_id', v)}>
                            <SelectTrigger id="blackout-court">
                                <SelectValue placeholder="Whole club" />
                            </SelectTrigger>
                            <SelectContent>
                                {courts.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.court_id} />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="blackout-start">From</Label>
                            <Input
                                id="blackout-start"
                                type="datetime-local"
                                value={data.starts_at}
                                onChange={(e) => setData('starts_at', e.target.value)}
                            />
                            <InputError message={errors.starts_at} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="blackout-end">To</Label>
                            <Input
                                id="blackout-end"
                                type="datetime-local"
                                value={data.ends_at}
                                onChange={(e) => setData('ends_at', e.target.value)}
                            />
                            <InputError message={errors.ends_at} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="blackout-reason">Reason</Label>
                        <Input
                            id="blackout-reason"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                            placeholder="Resurfacing"
                        />
                        <InputError message={errors.reason} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add blackout
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function CourtsIndex({ courts, canManage }: CourtsIndexProps) {
    const deleteCourt = (court: Court) => {
        if (confirm(`Delete ${court.name}? This cannot be undone.`)) {
            router.delete(route('courts.destroy', court.id), { preserveScroll: true });
        }
    };

    const deleteBlackout = (id: number) => router.delete(route('blackouts.destroy', id), { preserveScroll: true });

    return (
        <ClubLayout title="Courts">
            <div className="mx-auto max-w-4xl space-y-8">
                <header className="flex items-end justify-between">
                    <div className="space-y-1">
                        <p className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Facilities</p>
                        <h1 className="text-2xl font-semibold tracking-tight">Courts</h1>
                        <p className="text-muted-foreground text-sm">
                            {courts.length} court{courts.length === 1 ? '' : 's'}
                        </p>
                    </div>
                    {canManage && (
                        <div className="flex gap-2">
                            <BlackoutDialog courts={courts} />
                            <CourtFormDialog
                                title="New court"
                                submitLabel="Create court"
                                initial={{ name: '', surface: 'hard', is_active: true }}
                                method="post"
                                action={route('courts.store')}
                                trigger={
                                    <Button>
                                        <Plus /> New court
                                    </Button>
                                }
                            />
                        </div>
                    )}
                </header>

                {courts.length === 0 ? (
                    <p className="text-muted-foreground text-sm">No courts yet.{canManage ? ' Add your first court to get started.' : ''}</p>
                ) : (
                    <ul className="space-y-4">
                        {courts.map((court, index) => (
                            <li key={court.id} className="border-border bg-card rounded-xl border p-5">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex items-center gap-4">
                                        <span className="text-display text-muted-foreground text-3xl leading-none">
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium">{court.name}</span>
                                                {court.surface && (
                                                    <Badge variant="outline" className="capitalize">
                                                        {court.surface}
                                                    </Badge>
                                                )}
                                                {!court.is_active && <Badge variant="secondary">inactive</Badge>}
                                            </div>
                                        </div>
                                    </div>

                                    {canManage && (
                                        <div className="flex items-center gap-2">
                                            <AvailabilityEditor court={court} />
                                            <CourtFormDialog
                                                title={`Edit ${court.name}`}
                                                submitLabel="Save changes"
                                                initial={{
                                                    name: court.name,
                                                    surface: court.surface ?? 'hard',
                                                    is_active: court.is_active,
                                                }}
                                                method="put"
                                                action={route('courts.update', court.id)}
                                                trigger={
                                                    <Button variant="outline" size="sm">
                                                        Edit
                                                    </Button>
                                                }
                                            />
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => deleteCourt(court)}
                                                aria-label={`Delete ${court.name}`}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                    )}
                                </div>

                                <Separator className="my-4" />

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <p className="text-muted-foreground text-xs font-medium tracking-wider uppercase">Weekly hours</p>
                                        {court.availability.length === 0 ? (
                                            <p className="text-muted-foreground text-sm">No windows set.</p>
                                        ) : (
                                            <ul className="space-y-1 text-sm">
                                                {court.availability.map((w) => (
                                                    <li key={w.id} className="flex items-center justify-between">
                                                        <span className="text-muted-foreground">{DAY_LABELS[w.day_of_week]}</span>
                                                        <span className="text-display">
                                                            {w.opens_at}–{w.closes_at}
                                                        </span>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <p className="text-muted-foreground text-xs font-medium tracking-wider uppercase">Blackouts</p>
                                        {court.blackouts.length === 0 ? (
                                            <p className="text-muted-foreground text-sm">None scheduled.</p>
                                        ) : (
                                            <ul className="space-y-1 text-sm">
                                                {court.blackouts.map((b) => (
                                                    <li key={b.id} className="flex items-center justify-between gap-2">
                                                        <span className="text-muted-foreground">
                                                            {b.starts_at?.slice(0, 16).replace('T', ' ')} →{' '}
                                                            {b.ends_at?.slice(0, 16).replace('T', ' ')}
                                                            {b.reason ? ` · ${b.reason}` : ''}
                                                        </span>
                                                        {canManage && (
                                                            <button
                                                                type="button"
                                                                onClick={() => deleteBlackout(b.id)}
                                                                className="text-muted-foreground hover:text-destructive"
                                                                aria-label="Remove blackout"
                                                            >
                                                                <Trash2 className="size-4" />
                                                            </button>
                                                        )}
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </ClubLayout>
    );
}
