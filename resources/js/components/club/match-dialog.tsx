import { router, useForm } from '@inertiajs/react';
import { ImagePlus, X } from 'lucide-react';
import { useRef } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

export interface Player {
    id: number;
    name: string;
    partner?: string | null;
}

/** Display label for a match side — "Name & Partner" for doubles, just the name otherwise. */
export function sideLabel(player: Player | null): string {
    if (!player) return 'TBD';
    return player.partner ? `${player.name} & ${player.partner}` : player.name;
}

export interface Attachment {
    id: number;
    url: string;
    name: string | null;
}

export interface Match {
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

interface MatchForm {
    winner_id: string;
    score: string;
    notes: string;
    [key: string]: string;
}

/**
 * The detail/edit dialog for a single match — shared by the single-elimination bracket and
 * the round-robin fixtures. Lets a manager set the winner + score + notes and upload images;
 * read-only otherwise. Recording a winner advances them (single-elim) on the server.
 */
export function MatchDialog({ match, canManage, onClose }: { match: Match; canManage: boolean; onClose: () => void }) {
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
                        {sideLabel(match.playerOne)} vs {sideLabel(match.playerTwo)}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-5">
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
                                        {sideLabel(p)}
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
