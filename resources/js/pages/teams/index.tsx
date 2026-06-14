import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface TeamRow {
    id: number;
    name: string;
    players_count: number;
}

interface TeamsIndexProps {
    teams: TeamRow[];
    canManage: boolean;
}

interface TeamForm {
    name: string;
    [key: string]: string;
}

function CreateTeamDialog() {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<TeamForm>({ name: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('teams.store'), {
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
                <Button>
                    <Plus /> New team
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>New team</DialogTitle>
                        <DialogDescription>A squad of club members. Manage its roster from its page.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="team-name">Name</Label>
                        <Input
                            id="team-name"
                            autoFocus
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="First VII"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Create team
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function TeamsIndex({ teams, canManage }: TeamsIndexProps) {
    const deleteTeam = (team: TeamRow) => {
        if (confirm(`Delete ${team.name}? This cannot be undone.`)) {
            router.delete(route('teams.destroy', team.id), { preserveScroll: true });
        }
    };

    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title="Teams" />

            <div className="mx-auto max-w-4xl space-y-8 p-8">
                <header className="flex items-end justify-between">
                    <div className="space-y-1">
                        <p className="text-xs font-medium tracking-[0.2em] text-muted-foreground uppercase">Club</p>
                        <h1 className="text-2xl font-semibold tracking-tight">Teams</h1>
                        <p className="text-sm text-muted-foreground">
                            {teams.length} team{teams.length === 1 ? '' : 's'}
                        </p>
                    </div>
                    {canManage && <CreateTeamDialog />}
                </header>

                {teams.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No teams yet.{canManage ? ' Create your first to start building a roster.' : ''}
                    </p>
                ) : (
                    <ul className="space-y-4">
                        {teams.map((team, index) => (
                            <li key={team.id} className="rounded-xl border border-border bg-card p-5">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex items-center gap-4">
                                        <span className="text-display text-3xl leading-none text-muted-foreground">
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <div className="space-y-1">
                                            <Link
                                                href={route('teams.show', team.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {team.name}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">
                                                <span className="text-display">{team.players_count}</span> player
                                                {team.players_count === 1 ? '' : 's'}
                                            </p>
                                        </div>
                                    </div>

                                    {canManage && (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => deleteTeam(team)}
                                            aria-label={`Delete ${team.name}`}
                                        >
                                            <Trash2 />
                                        </Button>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
}
