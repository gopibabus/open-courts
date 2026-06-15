import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Trophy } from 'lucide-react';
import { Fragment, useMemo, useState } from 'react';

import { MatchDialog, sideLabel, type Match, type Player } from '@/components/club/match-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ClubLayout from '@/layouts/club-layout';
import { cn } from '@/lib/utils';

interface Round {
    name: string;
    label: string;
    matches: Match[];
}

interface StandingRow {
    userId: number;
    name: string | null;
    partner: string | null;
    played: number;
    won: number;
    lost: number;
    points: number;
}

interface BracketProps {
    tournament: { id: number; name: string };
    category: { id: number; name: string; type: string; format: string };
    rounds: Round[];
    standings: StandingRow[];
    fixtures: Match[];
    hasSchedule: boolean;
    canManage: boolean;
}

const SLOT = 88; // px of vertical space per first-round match
const CARD_W = 168; // px match card width

function PlayerLine({ player, won, decided }: { player: Player | null; won: boolean; decided: boolean }) {
    return (
        <div
            className={cn(
                'flex items-center justify-between gap-2 rounded-md px-3 py-1.5 text-xs font-semibold text-white transition',
                won ? 'bg-white/25' : decided ? 'opacity-50' : '',
            )}
        >
            <span className="truncate">{sideLabel(player)}</span>
            {won && <Trophy className="size-3 shrink-0 text-amber-300" />}
        </div>
    );
}

function MatchCard({ match, onClick }: { match: Match; onClick: () => void }) {
    const decided = match.winnerId !== null;
    return (
        <button
            type="button"
            onClick={onClick}
            style={{ width: CARD_W }}
            className="group space-y-1 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-700 p-1.5 text-left shadow-lg shadow-blue-950/40 ring-offset-2 ring-offset-neutral-950 transition hover:from-blue-500 hover:to-indigo-600 focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:outline-none"
        >
            <PlayerLine player={match.playerOne} won={match.winnerId === match.playerOne?.id} decided={decided} />
            <PlayerLine player={match.playerTwo} won={match.winnerId === match.playerTwo?.id} decided={decided} />
        </button>
    );
}

/** A bracket connector cell joining a pair of matches into the next round. */
function Connector({ side, lines }: { side: 'left' | 'right'; lines: number }) {
    const color = 'border-white/25';
    return (
        <div className="flex w-6 flex-col justify-around">
            {Array.from({ length: lines }).map((_, i) => (
                <div key={i} className="relative flex-1">
                    {side === 'left' ? (
                        <>
                            <div className={cn('absolute top-1/4 right-1/2 left-0 border-t-2', color)} />
                            <div className={cn('absolute right-1/2 bottom-1/4 left-0 border-t-2', color)} />
                            <div className={cn('absolute top-1/4 right-1/2 bottom-1/4 border-r-2', color)} />
                            <div className={cn('absolute top-1/2 right-0 left-1/2 border-t-2', color)} />
                        </>
                    ) : (
                        <>
                            <div className={cn('absolute top-1/4 right-0 left-1/2 border-t-2', color)} />
                            <div className={cn('absolute right-0 bottom-1/4 left-1/2 border-t-2', color)} />
                            <div className={cn('absolute top-1/4 bottom-1/4 left-1/2 border-r-2', color)} />
                            <div className={cn('absolute top-1/2 right-1/2 left-0 border-t-2', color)} />
                        </>
                    )}
                </div>
            ))}
        </div>
    );
}

function Side({ columns, side, onSelect }: { columns: Match[][]; side: 'left' | 'right'; onSelect: (m: Match) => void }) {
    return (
        <div className="flex items-stretch">
            {columns.map((col, i) => (
                <Fragment key={i}>
                    <div className="flex flex-col justify-around">
                        {col.map((m) => (
                            <div key={m.id} className="flex flex-1 items-center">
                                <MatchCard match={m} onClick={() => onSelect(m)} />
                            </div>
                        ))}
                    </div>
                    {i < columns.length - 1 && <Connector side={side} lines={Math.min(col.length, columns[i + 1].length)} />}
                </Fragment>
            ))}
        </div>
    );
}

/** The dark single-elimination bracket: two halves converging on the centre final + trophy. */
function BracketTree({ rounds, onSelect }: { rounds: Round[]; onSelect: (m: Match) => void }) {
    const layout = useMemo(() => {
        const finalRound = rounds[rounds.length - 1];
        const nonFinal = rounds.slice(0, -1);
        const leftCols = nonFinal.map((r) => r.matches.filter((m) => m.position < r.matches.length / 2));
        const rightCols = nonFinal.map((r) => r.matches.filter((m) => m.position >= r.matches.length / 2)).reverse();
        const firstColCount = leftCols[0]?.length ?? finalRound.matches.length;
        return { leftCols, rightCols, finalMatch: finalRound.matches[0], height: Math.max(firstColCount, 1) * SLOT };
    }, [rounds]);

    return (
        <div className="overflow-x-auto rounded-2xl bg-neutral-950 p-6 ring-1 ring-white/10">
            <div className="flex items-stretch justify-center" style={{ height: layout.height, minWidth: 'min-content' }}>
                <Side columns={layout.leftCols} side="left" onSelect={onSelect} />
                {layout.leftCols.length > 0 && (
                    <div className="flex items-center">
                        <div className="h-0.5 w-5 bg-white/25" />
                    </div>
                )}
                <div className="flex flex-col items-center justify-center gap-3 px-2">
                    <Trophy className="size-10 text-amber-400 drop-shadow-[0_0_12px_rgba(251,191,36,0.4)]" />
                    {layout.finalMatch && <MatchCard match={layout.finalMatch} onClick={() => onSelect(layout.finalMatch!)} />}
                    <span className="text-[10px] font-medium tracking-[0.25em] text-white/50 uppercase">Final</span>
                </div>
                {layout.rightCols.length > 0 && (
                    <div className="flex items-center">
                        <div className="h-0.5 w-5 bg-white/25" />
                    </div>
                )}
                <Side columns={layout.rightCols} side="right" onSelect={onSelect} />
            </div>
        </div>
    );
}

/** The round-robin view: a standings table + the full fixture list. */
function RoundRobin({ standings, fixtures, onSelect }: { standings: StandingRow[]; fixtures: Match[]; onSelect: (m: Match) => void }) {
    return (
        <div className="grid gap-6 lg:grid-cols-5">
            <section className="space-y-3 lg:col-span-2">
                <h2 className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Standings</h2>
                <div className="border-border overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-muted-foreground border-border border-b text-xs">
                                <th className="w-8 px-3 py-2 text-left font-medium">#</th>
                                <th className="px-3 py-2 text-left font-medium">Player</th>
                                <th className="w-8 px-2 py-2 text-center font-medium">P</th>
                                <th className="w-8 px-2 py-2 text-center font-medium">W</th>
                                <th className="w-8 px-2 py-2 text-center font-medium">L</th>
                                <th className="w-10 px-2 py-2 text-center font-medium">Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            {standings.map((row, i) => (
                                <tr key={row.userId} className="border-border border-b last:border-0">
                                    <td className="text-display text-muted-foreground px-3 py-2">{i + 1}</td>
                                    <td className="truncate px-3 py-2 font-medium">
                                        {row.name}
                                        {row.partner && <span className="text-muted-foreground font-normal"> &amp; {row.partner}</span>}
                                    </td>
                                    <td className="text-display px-2 py-2 text-center">{row.played}</td>
                                    <td className="text-display px-2 py-2 text-center">{row.won}</td>
                                    <td className="text-display px-2 py-2 text-center">{row.lost}</td>
                                    <td className="text-display px-2 py-2 text-center font-semibold">{row.points}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="space-y-3 lg:col-span-3">
                <h2 className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Fixtures</h2>
                <ul className="divide-border border-border divide-y rounded-xl border">
                    {fixtures.map((m) => (
                        <li key={m.id}>
                            <button
                                type="button"
                                onClick={() => onSelect(m)}
                                className="hover:bg-accent flex w-full items-center justify-between gap-3 p-3 text-left text-sm transition"
                            >
                                <span className="min-w-0 truncate">
                                    <span className={cn(m.winnerId === m.playerOne?.id && 'font-semibold')}>{sideLabel(m.playerOne)}</span>
                                    <span className="text-muted-foreground"> vs </span>
                                    <span className={cn(m.winnerId === m.playerTwo?.id && 'font-semibold')}>{sideLabel(m.playerTwo)}</span>
                                </span>
                                <span className="flex shrink-0 items-center gap-2">
                                    {m.score && <span className="text-display text-muted-foreground text-xs">{m.score}</span>}
                                    {m.status === 'completed' ? (
                                        <Badge variant="outline">Result</Badge>
                                    ) : (
                                        <span className="text-muted-foreground text-xs">Scheduled</span>
                                    )}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            </section>
        </div>
    );
}

const FORMAT_LABEL: Record<string, string> = {
    single_elimination: 'Single elimination',
    round_robin: 'Round robin',
};

export default function BracketPage({ tournament, category, rounds, standings, fixtures, hasSchedule, canManage }: BracketProps) {
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const isRoundRobin = category.format === 'round_robin';

    const allMatches = useMemo(() => (isRoundRobin ? fixtures : rounds.flatMap((r) => r.matches)), [isRoundRobin, fixtures, rounds]);
    const selected = useMemo(() => allMatches.find((m) => m.id === selectedId) ?? null, [allMatches, selectedId]);

    const generate = () => {
        if (hasSchedule && !confirm('Regenerate the draw? This replaces all current matches and results for this category.')) return;
        router.post(route('tournaments.bracket.generate', category.id), {}, { preserveScroll: true });
    };

    return (
        <ClubLayout title={`${category.name} draw`}>
            <div className="space-y-6">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div className="space-y-1">
                        <Link
                            href={route('tournaments.show', tournament.id)}
                            className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-xs"
                        >
                            <ArrowLeft className="size-3.5" /> {tournament.name}
                        </Link>
                        <h1 className="text-2xl font-semibold tracking-tight">{category.name}</h1>
                        <p className="text-muted-foreground text-sm capitalize">
                            {category.type} · {FORMAT_LABEL[category.format] ?? category.format}
                        </p>
                    </div>
                    {canManage && <Button onClick={generate}>{hasSchedule ? 'Regenerate draw' : 'Generate draw'}</Button>}
                </header>

                {!hasSchedule ? (
                    <div className="border-border rounded-xl border border-dashed p-12 text-center">
                        <Trophy className="text-muted-foreground mx-auto size-8" />
                        <p className="mt-3 font-medium">No draw yet</p>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {canManage
                                ? 'Generate the draw from this category’s confirmed entrants (at least two are needed).'
                                : 'The organisers haven’t generated the draw for this category yet.'}
                        </p>
                    </div>
                ) : isRoundRobin ? (
                    <RoundRobin standings={standings} fixtures={fixtures} onSelect={(m) => setSelectedId(m.id)} />
                ) : (
                    <BracketTree rounds={rounds} onSelect={(m) => setSelectedId(m.id)} />
                )}
            </div>

            {selected && <MatchDialog match={selected} canManage={canManage} onClose={() => setSelectedId(null)} />}
        </ClubLayout>
    );
}
