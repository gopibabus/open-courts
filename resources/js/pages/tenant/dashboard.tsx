import { Head } from '@inertiajs/react';

interface Court {
    id: number;
    name: string;
    surface: string | null;
}

interface Club {
    id: string;
    name: string;
    slug: string;
}

interface TenantDashboardProps {
    club: Club;
    roles: string[];
    courts: Court[];
}

/**
 * Per-club dashboard, served on <club>.<central_domain>. This is a deliberately
 * minimal skeleton screen whose only job is to prove the multi-tenant pipeline:
 * the data below is scoped to the current club, and `roles` are the signed-in
 * user's roles *within this club*.
 */
export default function TenantDashboard({ club, roles, courts }: TenantDashboardProps) {
    return (
        <>
            <Head title={`${club.name} — Dashboard`} />

            <div className="mx-auto max-w-3xl space-y-8 p-8">
                <header className="space-y-1">
                    <p className="text-sm font-medium text-neutral-500">Club workspace</p>
                    <h1 className="text-2xl font-semibold tracking-tight">{club.name}</h1>
                    <p className="text-sm text-neutral-500">
                        tenant id: <code>{club.id}</code>
                    </p>
                </header>

                <section className="space-y-2">
                    <h2 className="text-sm font-semibold text-neutral-500 uppercase">Your roles here</h2>
                    {roles.length > 0 ? (
                        <ul className="flex flex-wrap gap-2">
                            {roles.map((role) => (
                                <li
                                    key={role}
                                    className="rounded-full border border-neutral-300 px-3 py-1 text-sm dark:border-neutral-700"
                                >
                                    {role}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-neutral-500">No roles assigned in this club yet.</p>
                    )}
                </section>

                <section className="space-y-2">
                    <h2 className="text-sm font-semibold text-neutral-500 uppercase">Courts</h2>
                    {courts.length > 0 ? (
                        <ul className="divide-y divide-neutral-200 rounded-xl border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
                            {courts.map((court) => (
                                <li key={court.id} className="flex items-center justify-between px-4 py-3">
                                    <span className="font-medium">{court.name}</span>
                                    <span className="text-sm text-neutral-500">{court.surface ?? '—'}</span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-neutral-500">This club has no courts yet.</p>
                    )}
                </section>
            </div>
        </>
    );
}
