import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ClubLayout from '@/layouts/club-layout';

interface TournamentRow {
    id: number;
    name: string;
    status: string;
    format: string;
    starts_on: string | null;
    ends_on: string | null;
    registration_opens_on: string | null;
    registration_closes_on: string | null;
    categories_count: number;
}

interface TournamentsIndexProps {
    tournaments: TournamentRow[];
    canManage: boolean;
}

const FORMAT_LABELS: Record<string, string> = {
    single_elimination: 'Single elimination',
    round_robin: 'Round robin',
};

// Color appears only to signal state — open registration is the one "live" state.
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

export default function TournamentsIndex({ tournaments, canManage }: TournamentsIndexProps) {
    return (
        <ClubLayout title="Tournaments">
            <div className="space-y-8">
                <header className="flex items-end justify-between">
                    <div className="space-y-1">
                        <p className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Club</p>
                        <h1 className="text-2xl font-semibold tracking-tight">Tournaments</h1>
                        <p className="text-muted-foreground text-sm">
                            {tournaments.length} tournament{tournaments.length === 1 ? '' : 's'}
                        </p>
                    </div>
                    {canManage && (
                        <Button asChild>
                            <Link href={route('tournaments.create')}>
                                <Plus /> New tournament
                            </Link>
                        </Button>
                    )}
                </header>

                {tournaments.length === 0 ? (
                    <p className="text-muted-foreground text-sm">No tournaments yet.{canManage ? ' Create your first to get started.' : ''}</p>
                ) : (
                    <ul className="space-y-4">
                        {tournaments.map((t, index) => (
                            <li key={t.id} className="border-border bg-card rounded-xl border p-5">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex items-center gap-4">
                                        <span className="text-display text-muted-foreground text-3xl leading-none">
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <Link href={route('tournaments.show', t.id)} className="font-medium hover:underline">
                                                    {t.name}
                                                </Link>
                                                <Badge variant={statusVariant(t.status)} className="capitalize">
                                                    {t.status}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-xs">
                                                {FORMAT_LABELS[t.format] ?? t.format} · <span className="text-display">{t.categories_count}</span>{' '}
                                                categories
                                            </p>
                                        </div>
                                    </div>

                                    <div className="text-muted-foreground text-right text-xs">
                                        <div>
                                            Plays <span className="text-display">{dateRange(t.starts_on, t.ends_on)}</span>
                                        </div>
                                        <div>
                                            Registration{' '}
                                            <span className="text-display">{dateRange(t.registration_opens_on, t.registration_closes_on)}</span>
                                        </div>
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
