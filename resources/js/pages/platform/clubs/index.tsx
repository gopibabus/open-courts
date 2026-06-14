import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, LogIn, Pause, Play } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface ClubCounts {
    members: number;
    courts: number;
    tournaments: number;
}

interface Club {
    id: string;
    name: string;
    slug: string;
    status: 'active' | 'suspended';
    createdAt: string | null;
    counts: ClubCounts;
}

interface ClubsIndexProps {
    clubs: Club[];
    centralDomain: string;
}

// Build the absolute URL of a club's subdomain, preserving the current scheme + port.
const clubUrl = (slug: string, centralDomain: string) => {
    if (typeof window === 'undefined') return `http://${slug}.${centralDomain}/`;
    const { protocol, port } = window.location;
    const suffix = port && !['80', '443'].includes(port) ? `:${port}` : '';
    return `${protocol}//${slug}.${centralDomain}${suffix}/`;
};

export default function ClubsIndex({ clubs, centralDomain }: ClubsIndexProps) {
    const suspend = (club: Club) =>
        router.post(route('platform.clubs.suspend', club.id), {}, { preserveScroll: true });

    const reactivate = (club: Club) =>
        router.post(route('platform.clubs.reactivate', club.id), {}, { preserveScroll: true });

    const impersonate = (club: Club) =>
        router.post(route('platform.clubs.impersonate', club.id), {});

    return (
        <>
            <Head title="Clubs — Platform admin" />

            <div className="mx-auto max-w-5xl space-y-8 p-8">
                <header className="space-y-1">
                    <p className="text-xs font-medium tracking-widest text-neutral-500 uppercase">Platform admin</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Clubs</h1>
                    <p className="text-sm text-neutral-500">
                        Every club on the platform. Suspend a club to freeze its workspace, or impersonate its owner.
                    </p>
                </header>

                {clubs.length === 0 ? (
                    <p className="text-sm text-neutral-500">No clubs have been registered yet.</p>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-neutral-200 text-xs tracking-wide text-neutral-500 uppercase dark:border-neutral-800">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Club</th>
                                    <th className="px-4 py-3 text-right font-medium">Members</th>
                                    <th className="px-4 py-3 text-right font-medium">Courts</th>
                                    <th className="px-4 py-3 text-right font-medium">Tournaments</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {clubs.map((club) => (
                                    <tr key={club.id} className="align-middle">
                                        <td className="px-4 py-3">
                                            <Link
                                                href={route('platform.clubs.show', club.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {club.name}
                                            </Link>
                                            <div className="text-xs text-neutral-500">
                                                {club.slug}.{centralDomain}
                                            </div>
                                        </td>
                                        <td className="text-display px-4 py-3 text-right text-lg tabular-nums">
                                            {club.counts.members}
                                        </td>
                                        <td className="text-display px-4 py-3 text-right text-lg tabular-nums">
                                            {club.counts.courts}
                                        </td>
                                        <td className="text-display px-4 py-3 text-right text-lg tabular-nums">
                                            {club.counts.tournaments}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={club.status === 'active' ? 'outline' : 'secondary'}>
                                                {club.status === 'active' ? 'Active' : 'Suspended'}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center justify-end gap-2">
                                                {club.status === 'active' ? (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => suspend(club)}
                                                    >
                                                        <Pause /> Suspend
                                                    </Button>
                                                ) : (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => reactivate(club)}
                                                    >
                                                        <Play /> Reactivate
                                                    </Button>
                                                )}
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => impersonate(club)}
                                                >
                                                    <LogIn /> Impersonate
                                                </Button>
                                                <a
                                                    href={clubUrl(club.slug, centralDomain)}
                                                    className="inline-flex h-9 items-center gap-1.5 rounded-md px-3 text-sm text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100"
                                                >
                                                    <ExternalLink className="h-4 w-4" /> Open
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
