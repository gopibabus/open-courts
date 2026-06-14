import { Head, router, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Member {
    id: number;
    name: string;
    email: string;
    roles: string[];
}

interface PendingInvitation {
    id: number;
    email: string;
    role: string;
    expires_at: string;
    created_at: string | null;
}

interface MembersIndexProps {
    members: Member[];
    pendingInvitations: PendingInvitation[];
    roles: string[];
    can: { manageMembers: boolean };
}

interface InviteForm {
    email: string;
    role: string;
}

export default function MembersIndex({ members, pendingInvitations, roles, can }: MembersIndexProps) {
    const [inviteOpen, setInviteOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm<InviteForm>({
        email: '',
        role: roles[roles.length - 1] ?? '',
    });

    const submitInvite: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('membership.invitations.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset('email');
                setInviteOpen(false);
            },
        });
    };

    const changeRole = (memberId: number, role: string) => {
        router.patch(
            route('membership.members.update', memberId),
            { role },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Members" />

            <div className="mx-auto max-w-3xl space-y-8 p-8">
                <header className="flex items-start justify-between gap-4">
                    <div className="space-y-1">
                        <p className="text-sm font-medium text-muted-foreground">Club workspace</p>
                        <h1 className="text-2xl font-semibold tracking-tight">Members</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage who belongs to this club and what they can do.
                        </p>
                    </div>

                    {can.manageMembers && (
                        <Dialog open={inviteOpen} onOpenChange={setInviteOpen}>
                            <DialogTrigger asChild>
                                <Button>Invite member</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <form onSubmit={submitInvite} className="space-y-6">
                                    <DialogHeader>
                                        <DialogTitle>Invite a member</DialogTitle>
                                        <DialogDescription>
                                            They'll get an email with a link to join this club.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div className="grid gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="email">Email</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                required
                                                autoFocus
                                                value={data.email}
                                                onChange={(e) => setData('email', e.target.value.toLowerCase())}
                                                placeholder="player@example.com"
                                            />
                                            <InputError message={errors.email} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="role">Role</Label>
                                            <Select value={data.role} onValueChange={(value) => setData('role', value)}>
                                                <SelectTrigger id="role">
                                                    <SelectValue placeholder="Select a role" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {roles.map((role) => (
                                                        <SelectItem key={role} value={role}>
                                                            {role}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.role} />
                                        </div>
                                    </div>

                                    <DialogFooter>
                                        <Button type="submit" disabled={processing}>
                                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                            Send invite
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    )}
                </header>

                <section className="space-y-3">
                    <h2 className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">
                        Directory
                    </h2>
                    <ul className="divide-y divide-border rounded-xl border border-border">
                        {members.map((member) => (
                            <li key={member.id} className="flex items-center justify-between gap-4 px-4 py-3">
                                <div className="min-w-0">
                                    <p className="truncate font-medium">{member.name}</p>
                                    <p className="truncate text-sm text-muted-foreground">{member.email}</p>
                                </div>

                                {can.manageMembers ? (
                                    <Select
                                        value={member.roles[0] ?? ''}
                                        onValueChange={(role) => changeRole(member.id, role)}
                                    >
                                        <SelectTrigger className="w-40" aria-label={`Role for ${member.name}`}>
                                            <SelectValue placeholder="No role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roles.map((role) => (
                                                <SelectItem key={role} value={role}>
                                                    {role}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : (
                                    <div className="flex flex-wrap justify-end gap-1.5">
                                        {member.roles.length > 0 ? (
                                            member.roles.map((role) => (
                                                <Badge key={role} variant="outline">
                                                    {role}
                                                </Badge>
                                            ))
                                        ) : (
                                            <span className="text-sm text-muted-foreground">—</span>
                                        )}
                                    </div>
                                )}
                            </li>
                        ))}
                        {members.length === 0 && (
                            <li className="px-4 py-6 text-sm text-muted-foreground">No members yet.</li>
                        )}
                    </ul>
                </section>

                {can.manageMembers && (
                    <section className="space-y-3">
                        <h2 className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">
                            Pending invitations
                        </h2>
                        {pendingInvitations.length > 0 ? (
                            <ul className="divide-y divide-border rounded-xl border border-border">
                                {pendingInvitations.map((invitation) => (
                                    <li
                                        key={invitation.id}
                                        className="flex items-center justify-between gap-4 px-4 py-3"
                                    >
                                        <span className="truncate text-sm">{invitation.email}</span>
                                        <Badge variant="secondary">{invitation.role}</Badge>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-muted-foreground">No pending invitations.</p>
                        )}
                    </section>
                )}
            </div>
        </>
    );
}
