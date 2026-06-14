import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus } from 'lucide-react';
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

interface Entrant {
    id: number;
    user: { id: number; name: string } | null;
    partner: { id: number; name: string } | null;
    seed: number | null;
    status: string;
}

interface Category {
    id: number;
    name: string;
    type: string;
    max_entrants: number | null;
    entrants: Entrant[];
}

interface Tournament {
    id: number;
    name: string;
    status: string;
    format: string;
    starts_on: string | null;
    ends_on: string | null;
    registration_opens_on: string | null;
    registration_closes_on: string | null;
}

interface TypeOption {
    value: string;
    label: string;
}

interface ShowTournamentProps {
    tournament: Tournament;
    categories: Category[];
    categoryTypes: TypeOption[];
    canManage: boolean;
}

const FORMAT_LABELS: Record<string, string> = {
    single_elimination: 'Single elimination',
    round_robin: 'Round robin',
};

interface CategoryForm {
    name: string;
    type: string;
    max_entrants: string;
    [key: string]: string;
}

function AddCategoryDialog({ tournamentId, types }: { tournamentId: number; types: TypeOption[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<CategoryForm>({
        name: '',
        type: types[0]?.value ?? 'singles',
        max_entrants: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tournaments.categories.store', tournamentId), {
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
                <Button variant="outline" size="sm">
                    <Plus /> Add category
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Add a category</DialogTitle>
                        <DialogDescription>An event entrants register into, e.g. Men's Singles.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="category-name">Name</Label>
                        <Input
                            id="category-name"
                            autoFocus
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Men's Singles"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="category-type">Type</Label>
                        <Select value={data.type} onValueChange={(v) => setData('type', v)}>
                            <SelectTrigger id="category-type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {types.map((t) => (
                                    <SelectItem key={t.value} value={t.value}>
                                        {t.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="category-max">Max entrants (optional)</Label>
                        <Input
                            id="category-max"
                            type="number"
                            min={2}
                            value={data.max_entrants}
                            onChange={(e) => setData('max_entrants', e.target.value)}
                            placeholder="Unlimited"
                        />
                        <InputError message={errors.max_entrants} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Add category
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

interface OpenRegistrationForm {
    registration_opens_on: string;
    registration_closes_on: string;
    [key: string]: string;
}

function OpenRegistrationDialog({ tournament }: { tournament: Tournament }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors } = useForm<OpenRegistrationForm>({
        registration_opens_on: tournament.registration_opens_on ?? '',
        registration_closes_on: tournament.registration_closes_on ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tournaments.open-registration', tournament.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">{tournament.status === 'open' ? 'Update registration' : 'Open registration'}</Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Open registration</DialogTitle>
                        <DialogDescription>Set the window members may register within.</DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="reg-opens">Opens on</Label>
                            <Input
                                id="reg-opens"
                                type="date"
                                value={data.registration_opens_on}
                                onChange={(e) => setData('registration_opens_on', e.target.value)}
                            />
                            <InputError message={errors.registration_opens_on} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="reg-closes">Closes on</Label>
                            <Input
                                id="reg-closes"
                                type="date"
                                value={data.registration_closes_on}
                                onChange={(e) => setData('registration_closes_on', e.target.value)}
                            />
                            <InputError message={errors.registration_closes_on} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function statusVariant(status: string): 'secondary' | 'outline' | 'default' {
    if (status === 'open') return 'default';
    if (status === 'completed') return 'outline';
    return 'secondary';
}

function dateRange(from: string | null, to: string | null): string {
    if (!from && !to) return '—';
    if (from && to) return `${from} → ${to}`;
    return from ?? to ?? '—';
}

function CategoryCard({ tournament, category, canManage }: { tournament: Tournament; category: Category; canManage: boolean }) {
    // Active = anyone not withdrawn (capacity is measured against these).
    const active = category.entrants.filter((e) => e.status !== 'withdrawn');
    const isOpen = tournament.status === 'open';
    const atCapacity = category.max_entrants !== null && active.length >= category.max_entrants;
    // Empty payload — the entrant is the current user (server-side). `registration` is the
    // domain-rejection error key surfaced by RegisterEntrant via the controller.
    const { errors, post, processing } = useForm<Record<string, never>>({});

    const register = () => {
        post(route('tournaments.registrations.store', category.id), { preserveScroll: true });
    };

    const withdraw = (registrationId: number) => {
        router.delete(route('tournaments.registrations.destroy', registrationId), { preserveScroll: true });
    };

    return (
        <li className="border-border bg-card rounded-xl border p-5">
            <div className="flex items-start justify-between gap-4">
                <div className="space-y-1">
                    <div className="flex items-center gap-2">
                        <span className="font-medium">{category.name}</span>
                        <Badge variant="outline" className="capitalize">
                            {category.type}
                        </Badge>
                    </div>
                    <p className="text-muted-foreground text-xs">
                        <span className="text-display">{active.length}</span>
                        {category.max_entrants !== null && (
                            <>
                                {' / '}
                                <span className="text-display">{category.max_entrants}</span>
                            </>
                        )}{' '}
                        entrant{active.length === 1 ? '' : 's'}
                    </p>
                </div>

                {isOpen && (
                    <Button size="sm" onClick={register} disabled={processing || atCapacity}>
                        {atCapacity ? 'Full' : 'Register'}
                    </Button>
                )}
            </div>

            <InputError className="mt-2" message={errors.registration} />

            <Separator className="my-4" />

            {active.length === 0 ? (
                <p className="text-muted-foreground text-sm">No entrants yet.</p>
            ) : (
                <ul className="space-y-1 text-sm">
                    {active.map((e, index) => (
                        <li key={e.id} className="flex items-center justify-between gap-2">
                            <span className="flex items-center gap-3">
                                <span className="text-display text-muted-foreground">{e.seed ?? String(index + 1).padStart(2, '0')}</span>
                                <span>
                                    {e.user?.name ?? '—'}
                                    {e.partner && <span className="text-muted-foreground"> &amp; {e.partner.name}</span>}
                                </span>
                                {e.status !== 'confirmed' && (
                                    <Badge variant="secondary" className="capitalize">
                                        {e.status}
                                    </Badge>
                                )}
                            </span>
                            {canManage && (
                                <button type="button" onClick={() => withdraw(e.id)} className="text-muted-foreground hover:text-destructive text-xs">
                                    Withdraw
                                </button>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </li>
    );
}

export default function ShowTournament({ tournament, categories, categoryTypes, canManage }: ShowTournamentProps) {
    return (
        <ClubLayout title="Tournament">
            <div className="mx-auto max-w-4xl space-y-8">
                <header className="space-y-3">
                    <Link
                        href={route('tournaments.index')}
                        className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-xs"
                    >
                        <ArrowLeft className="size-3.5" /> Tournaments
                    </Link>
                    <div className="flex items-start justify-between gap-4">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-semibold tracking-tight">{tournament.name}</h1>
                                <Badge variant={statusVariant(tournament.status)} className="capitalize">
                                    {tournament.status}
                                </Badge>
                            </div>
                            <p className="text-muted-foreground text-sm">
                                {FORMAT_LABELS[tournament.format] ?? tournament.format} · plays{' '}
                                <span className="text-display">{dateRange(tournament.starts_on, tournament.ends_on)}</span> · registration{' '}
                                <span className="text-display">{dateRange(tournament.registration_opens_on, tournament.registration_closes_on)}</span>
                            </p>
                        </div>
                        {canManage && (
                            <div className="flex shrink-0 items-center gap-2">
                                <AddCategoryDialog tournamentId={tournament.id} types={categoryTypes} />
                                <OpenRegistrationDialog tournament={tournament} />
                            </div>
                        )}
                    </div>
                </header>

                <section className="space-y-4">
                    <h2 className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Categories</h2>
                    {categories.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No categories yet.{canManage ? ' Add one to let members register.' : ''}</p>
                    ) : (
                        <ul className="space-y-4">
                            {categories.map((category) => (
                                <CategoryCard key={category.id} tournament={tournament} category={category} canManage={canManage} />
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </ClubLayout>
    );
}
