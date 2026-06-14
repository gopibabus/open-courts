import { Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import ClubLayout from '@/layouts/club-layout';

interface TeamRow {
    id: number;
    name: string;
    players_count: number;
    tournament: { id: number; name: string } | null;
}

interface TeamsIndexProps {
    teams: TeamRow[];
    canManage: boolean;
}

/**
 * A read-only list of every team across the club's tournaments. Teams belong to a
 * tournament and are created from a tournament's page; this page links through to each
 * team's roster (and the tournament it plays in).
 */
export default function TeamsIndex({ teams, canManage }: TeamsIndexProps) {
    const deleteTeam = (team: TeamRow) => {
        if (confirm(`Delete ${team.name}? This cannot be undone.`)) {
            router.delete(route('teams.destroy', team.id), { preserveScroll: true });
        }
    };

    return (
        <ClubLayout title="Teams">
            <div className="mx-auto max-w-4xl space-y-8">
                <header className="space-y-1">
                    <p className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Club</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Teams</h1>
                    <p className="text-muted-foreground text-sm">
                        {teams.length} team{teams.length === 1 ? '' : 's'} across your tournaments. Teams are created from a{' '}
                        <Link href={route('tournaments.index')} className="hover:text-foreground underline">
                            tournament
                        </Link>
                        .
                    </p>
                </header>

                {teams.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No teams yet. Open a{' '}
                        <Link href={route('tournaments.index')} className="hover:text-foreground underline">
                            tournament
                        </Link>{' '}
                        to create one.
                    </p>
                ) : (
                    <ul className="space-y-4">
                        {teams.map((team, index) => (
                            <li key={team.id} className="border-border bg-card rounded-xl border p-5">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex items-center gap-4">
                                        <span className="text-display text-muted-foreground text-3xl leading-none">
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <div className="space-y-1">
                                            <Link href={route('teams.show', team.id)} className="font-medium hover:underline">
                                                {team.name}
                                            </Link>
                                            <p className="text-muted-foreground text-xs">
                                                <span className="text-display">{team.players_count}</span> player{team.players_count === 1 ? '' : 's'}
                                                {team.tournament && (
                                                    <>
                                                        {' · '}
                                                        <Link
                                                            href={route('tournaments.show', team.tournament.id)}
                                                            className="hover:text-foreground underline"
                                                        >
                                                            {team.tournament.name}
                                                        </Link>
                                                    </>
                                                )}
                                            </p>
                                        </div>
                                    </div>

                                    {canManage && (
                                        <Button variant="ghost" size="icon" onClick={() => deleteTeam(team)} aria-label={`Delete ${team.name}`}>
                                            <Trash2 />
                                        </Button>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </ClubLayout>
    );
}
