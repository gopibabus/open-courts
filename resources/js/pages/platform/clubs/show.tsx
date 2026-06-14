import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, LogIn, Pause, Play } from 'lucide-react';

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

interface ClubShowProps {
    club: Club;
    centralDomain: string;
}

const clubUrl = (slug: string, centralDomain: string) => {
    if (typeof window === 'undefined') return `http://${slug}.${centralDomain}/`;
    const { protocol, port } = window.location;
    const suffix = port && !['80', '443'].includes(port) ? `:${port}` : '';
    return `${protocol}//${slug}.${centralDomain}${suffix}/`;
};

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-xl border border-neutral-200 p-6 dark:border-neutral-800">
            <div className="text-display text-5xl leading-none tabular-nums">{value}</div>
            <div className="mt-2 text-xs tracking-widest text-neutral-500 uppercase">{label}</div>
        </div>
    );
}

export default function ClubShow({ club, centralDomain }: ClubShowProps) {
    const suspend = () => router.post(route('platform.clubs.suspend', club.id), {}, { preserveScroll: true });
    const reactivate = () => router.post(route('platform.clubs.reactivate', club.id), {}, { preserveScroll: true });
    const impersonate = () => router.post(route('platform.clubs.impersonate', club.id), {});

    return (
        <>
            <Head title={`${club.name} — Platform admin`} />

            <div className="mx-auto max-w-3xl space-y-8 p-8">
                <Link
                    href={route('platform.clubs.index')}
                    className="inline-flex items-center gap-1.5 text-sm text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100"
                >
                    <ArrowLeft className="h-4 w-4" /> All clubs
                </Link>

                <header className="flex items-start justify-between gap-4">
                    <div className="space-y-1">
                        <p className="text-xs font-medium tracking-widest text-neutral-500 uppercase">Platform admin</p>
                        <h1 className="text-2xl font-semibold tracking-tight">{club.name}</h1>
                        <p className="text-sm text-neutral-500">
                            {club.slug}.{centralDomain} · tenant id <code>{club.id}</code>
                        </p>
                    </div>
                    <Badge variant={club.status === 'active' ? 'outline' : 'secondary'}>
                        {club.status === 'active' ? 'Active' : 'Suspended'}
                    </Badge>
                </header>

                <section className="grid grid-cols-3 gap-4">
                    <Stat label="Members" value={club.counts.members} />
                    <Stat label="Courts" value={club.counts.courts} />
                    <Stat label="Tournaments" value={club.counts.tournaments} />
                </section>

                <section className="flex flex-wrap items-center gap-2">
                    {club.status === 'active' ? (
                        <Button variant="outline" onClick={suspend}>
                            <Pause /> Suspend club
                        </Button>
                    ) : (
                        <Button variant="outline" onClick={reactivate}>
                            <Play /> Reactivate club
                        </Button>
                    )}
                    <Button variant="ghost" onClick={impersonate}>
                        <LogIn /> Impersonate owner
                    </Button>
                    <a
                        href={clubUrl(club.slug, centralDomain)}
                        className="inline-flex h-10 items-center gap-1.5 rounded-md px-4 text-sm text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100"
                    >
                        <ExternalLink className="h-4 w-4" /> Open workspace
                    </a>
                </section>
            </div>
        </>
    );
}
