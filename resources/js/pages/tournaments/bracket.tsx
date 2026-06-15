import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ImagePlus, Trophy, X } from 'lucide-react';
import { Fragment, useMemo, useRef, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import ClubLayout from '@/layouts/club-layout';
import { cn } from '@/lib/utils';

interface Player {
    id: number;
    name: string;
}

interface Attachment {
    id: number;
    url: string;
    name: string | null;
}

interface Match {
    id: number;
    position: number;
    round: string;
    playerOne: Player | null;
    playerTwo: Player | null;
    winnerId: number | null;
    score: string | null;
    notes: string | null;
    status: string;
    attachments: Attachment[];
}

interface Round {
    name: string;
    label: string;
    matches: Match[];
}

interface BracketProps {
    tournament: { id: number; name: string };
    category: { id: number; name: string; type: string };
    rounds: Round[];
    hasBracket: boolean;
    canManage: boolean;
}

const SLOT = 88; // px of vertical space per first-round match
const CARD_W = 168; // px match card width

/** One player line inside a match card. */
function PlayerLine({ player, won, decided }: { player: Player | null; won: boolean; decided: boolean }) {
    return (
        <div
            className={cn(
                'flex items-center justify-between gap-2 rounded-md px-3 py-1.5 text-xs font-semibold text-white transition',
                won ? 'bg-white/25' : decided ? 'opacity-50' : '',
            )}
        >
            <span className="truncate">{player?.name ?? 'TBD'}</span>
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

/** One side of the bracket: columns of match cards with connectors between them. */
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

interface MatchForm {
    winner_id: string;
    score: string;
    notes: string;
    [key: string]: string;
}

function MatchDialog({ match, canManage, onClose }: { match: Match; canManage: boolean; onClose: () => void }) {
    const fileInput = useRef<HTMLInputElement>(null);
    const { data, setData, patch, processing, errors } = useForm<MatchForm>({
        winner_id: match.winnerId ? String(match.winnerId) : '',
        score: match.score ?? '',
        notes: match.notes ?? '',
    });

    const players = [match.playerOne, match.playerTwo].filter(Boolean) as Player[];
    const bothPresent = match.playerOne && match.playerTwo;

    const save = () => {
        patch(route('tournaments.matches.update', match.id), { preserveScroll: true, onSuccess: onClose });
    };

    const upload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        router.post(
            route('tournaments.matches.attachments.store', match.id),
            { image: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    if (fileInput.current) fileInput.current.value = '';
                },
            },
        );
    };

    const removeImage = (id: number) => {
        router.delete(route('tournaments.matches.attachments.destroy', id), { preserveScroll: true });
    };

    return (
        <Dialog open onOpenChange={(o) => !o && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{match.round}</DialogTitle>
                    <DialogDescription>
                        {match.playerOne?.name ?? 'TBD'} vs {match.playerTwo?.name ?? 'TBD'}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-5">
                    {/* Winner + score */}
                    {canManage && bothPresent && (
                        <div className="grid gap-2">
                            <Label>Winner</Label>
                            <div className="grid grid-cols-2 gap-2">
                                {players.map((p) => (
                                    <button
                                        key={p.id}
                                        type="button"
                                        onClick={() => setData('winner_id', data.winner_id === String(p.id) ? '' : String(p.id))}
                                        className={cn(
                                            'rounded-md border px-3 py-2 text-sm font-medium transition',
                                            data.winner_id === String(p.id)
                                                ? 'border-foreground bg-foreground text-background'
                                                : 'border-border hover:bg-accent',
                                        )}
                                    >
                                        {p.name}
                                    </button>
                                ))}
                            </div>
                            <InputError message={errors.winner_id} />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="match-score">Score</Label>
                        <Input
                            id="match-score"
                            value={data.score}
                            onChange={(e) => setData('score', e.target.value)}
                            placeholder="6-4 6-2"
                            maxLength={50}
                            disabled={!canManage}
                        />
                        <InputError message={errors.score} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="match-notes">Notes</Label>
                        <Textarea
                            id="match-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder="Match notes, conditions, highlights…"
                            rows={3}
                            disabled={!canManage}
                        />
                        <InputError message={errors.notes} />
                    </div>

                    {/* Images */}
                    <div className="grid gap-2">
                        <Label>Images</Label>
                        {match.attachments.length > 0 ? (
                            <div className="grid grid-cols-3 gap-2">
                                {match.attachments.map((a) => (
                                    <div key={a.id} className="group border-border relative overflow-hidden rounded-md border">
                                        <img src={a.url} alt={a.name ?? 'Match image'} className="aspect-square w-full object-cover" />
                                        {canManage && (
                                            <button
                                                type="button"
                                                onClick={() => removeImage(a.id)}
                                                className="absolute top-1 right-1 rounded-full bg-black/60 p-1 text-white opacity-0 transition group-hover:opacity-100"
                                                aria-label="Remove image"
                                            >
                                                <X className="size-3" />
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground text-sm">No images yet.</p>
                        )}
                        {canManage && (
                            <>
                                <input ref={fileInput} type="file" accept="image/*" onChange={upload} className="hidden" />
                                <Button type="button" variant="outline" size="sm" onClick={() => fileInput.current?.click()}>
                                    <ImagePlus /> Upload image
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {canManage && (
                    <DialogFooter>
                        <Button type="button" onClick={save} disabled={processing}>
                            Save match
                        </Button>
                    </DialogFooter>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default function BracketPage({ tournament, category, rounds, hasBracket, canManage }: BracketProps) {
    const [selectedId, setSelectedId] = useState<number | null>(null);

    const allMatches = useMemo(() => rounds.flatMap((r) => r.matches), [rounds]);
    const selected = useMemo(() => allMatches.find((m) => m.id === selectedId) ?? null, [allMatches, selectedId]);

    // Split rounds into a left half (outer→centre), the final, and a right half (centre→outer).
    const layout = useMemo(() => {
        if (rounds.length === 0) return null;
        const finalRound = rounds[rounds.length - 1];
        const nonFinal = rounds.slice(0, -1);
        const leftCols = nonFinal.map((r) => r.matches.filter((m) => m.position < r.matches.length / 2));
        const rightCols = nonFinal.map((r) => r.matches.filter((m) => m.position >= r.matches.length / 2)).reverse();
        const firstColCount = leftCols[0]?.length ?? finalRound.matches.length;
        return { leftCols, rightCols, finalMatch: finalRound.matches[0], height: Math.max(firstColCount, 1) * SLOT };
    }, [rounds]);

    const generate = () => {
        if (hasBracket && !confirm('Regenerate the bracket? This replaces all current matches and results for this category.')) return;
        router.post(route('tournaments.bracket.generate', category.id), {}, { preserveScroll: true });
    };

    return (
        <ClubLayout title={`${category.name} bracket`}>
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
                        <p className="text-muted-foreground text-sm capitalize">{category.type} · single elimination</p>
                    </div>
                    {canManage && <Button onClick={generate}>{hasBracket ? 'Regenerate bracket' : 'Generate bracket'}</Button>}
                </header>

                {!hasBracket || !layout ? (
                    <div className="border-border rounded-xl border border-dashed p-12 text-center">
                        <Trophy className="text-muted-foreground mx-auto size-8" />
                        <p className="mt-3 font-medium">No bracket yet</p>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {canManage
                                ? 'Generate the draw from this category’s confirmed entrants (at least two are needed).'
                                : 'The organisers haven’t generated the draw for this category yet.'}
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-2xl bg-neutral-950 p-6 ring-1 ring-white/10">
                        <div className="flex items-stretch justify-center" style={{ height: layout.height, minWidth: 'min-content' }}>
                            <Side columns={layout.leftCols} side="left" onSelect={(m) => setSelectedId(m.id)} />

                            {layout.leftCols.length > 0 && (
                                <div className="flex items-center">
                                    <div className="h-0.5 w-5 bg-white/25" />
                                </div>
                            )}

                            {/* Centre — the final + trophy */}
                            <div className="flex flex-col items-center justify-center gap-3 px-2">
                                <Trophy className="size-10 text-amber-400 drop-shadow-[0_0_12px_rgba(251,191,36,0.4)]" />
                                {layout.finalMatch && <MatchCard match={layout.finalMatch} onClick={() => setSelectedId(layout.finalMatch!.id)} />}
                                <span className="text-[10px] font-medium tracking-[0.25em] text-white/50 uppercase">Final</span>
                            </div>

                            {layout.rightCols.length > 0 && (
                                <div className="flex items-center">
                                    <div className="h-0.5 w-5 bg-white/25" />
                                </div>
                            )}

                            <Side columns={layout.rightCols} side="right" onSelect={(m) => setSelectedId(m.id)} />
                        </div>
                    </div>
                )}
            </div>

            {selected && <MatchDialog match={selected} canManage={canManage} onClose={() => setSelectedId(null)} />}
        </ClubLayout>
    );
}
