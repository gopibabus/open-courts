import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, UserPlus } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import ClubLayout from '@/layouts/club-layout';

interface Player {
    id: number;
    name: string;
}

interface Team {
    id: number;
    name: string;
    tournament: { id: number; name: string } | null;
}

interface ShowTeamProps {
    team: Team;
    roster: Player[];
    availableMembers: Player[];
    canManage: boolean;
}

interface AddPlayerForm {
    user_id: string;
    [key: string]: string;
}

function AddPlayerDialog({ team, members }: { team: Team; members: Player[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<AddPlayerForm>({ user_id: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('teams.players.store', team.id), {
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
                <Button size="sm" disabled={members.length === 0}>
                    <UserPlus /> Add player
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Add a player</DialogTitle>
                        <DialogDescription>
                            Pick a club member to add. Members already on a team in this tournament aren't listed — a member can be on only one team
                            per tournament.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="roster-member">Member</Label>
                        <Select value={data.user_id} onValueChange={(v) => setData('user_id', v)}>
                            <SelectTrigger id="roster-member">
                                <SelectValue placeholder="Select a member" />
                            </SelectTrigger>
                            <SelectContent>
                                {members.map((m) => (
                                    <SelectItem key={m.id} value={String(m.id)}>
                                        {m.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.user_id} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing || data.user_id === ''}>
                            Add player
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function ShowTeam({ team, roster, availableMembers, canManage }: ShowTeamProps) {
    const removePlayer = (player: Player) => {
        router.delete(route('teams.players.destroy', [team.id, player.id]), { preserveScroll: true });
    };

    return (
        <ClubLayout title="Team">
            <div className="mx-auto max-w-4xl space-y-8">
                <header className="space-y-3">
                    {team.tournament && (
                        <Link
                            href={route('tournaments.show', team.tournament.id)}
                            className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-xs"
                        >
                            <ArrowLeft className="size-3.5" /> {team.tournament.name}
                        </Link>
                    )}
                    <div className="flex items-start justify-between gap-4">
                        <div className="space-y-1">
                            <h1 className="text-2xl font-semibold tracking-tight">{team.name}</h1>
                            <p className="text-muted-foreground text-sm">
                                <span className="text-display">{roster.length}</span> player{roster.length === 1 ? '' : 's'} on the roster
                                {team.tournament && <> · {team.tournament.name}</>}
                            </p>
                        </div>
                        {canManage && <AddPlayerDialog team={team} members={availableMembers} />}
                    </div>
                </header>

                <section className="space-y-4">
                    <h2 className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Roster</h2>

                    {roster.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No players yet.{canManage ? ' Add club members to build the squad.' : ''}</p>
                    ) : (
                        <ul className="divide-border border-border bg-card divide-y rounded-xl border">
                            {roster.map((player, index) => (
                                <li key={player.id} className="flex items-center justify-between gap-4 p-4">
                                    <span className="flex items-center gap-4">
                                        <span className="text-display text-muted-foreground">{String(index + 1).padStart(2, '0')}</span>
                                        <span className="font-medium">{player.name}</span>
                                    </span>
                                    {canManage && (
                                        <button
                                            type="button"
                                            onClick={() => removePlayer(player)}
                                            className="text-muted-foreground hover:text-destructive text-xs"
                                        >
                                            Remove
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}

                    {canManage && availableMembers.length === 0 && roster.length > 0 && (
                        <>
                            <Separator />
                            <p className="text-muted-foreground text-xs">Every available club member is already on a team in this tournament.</p>
                        </>
                    )}
                </section>
            </div>
        </ClubLayout>
    );
}
