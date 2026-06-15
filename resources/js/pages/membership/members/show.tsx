import { Link } from '@inertiajs/react';
import { ArrowLeft, Award, Medal, Trophy } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import ClubLayout from '@/layouts/club-layout';

type Placement = 'champion' | 'runner_up' | 'semi_finalist';

interface PlayerProfile {
    id: number;
    name: string;
    email: string;
    roles: string[];
    memberSince: string | null;
    record: { played: number; won: number; lost: number; winPct: number; titles: number; finals: number };
    trophies: { tournament: string | null; tournamentId: number; category: string | null; placement: Placement }[];
    badges: { key: string; label: string }[];
    activity: { tournaments: number; teams: number; bookings: number };
    recentResults: {
        id: number;
        tournament: string | null;
        category: string | null;
        round: string;
        won: boolean;
        opponent: string | null;
        score: string | null;
    }[];
}

const PLACEMENT: Record<Placement, { label: string; icon: typeof Trophy }> = {
    champion: { label: 'Champion', icon: Trophy },
    runner_up: { label: 'Runner-up', icon: Medal },
    semi_finalist: { label: 'Semi-finalist', icon: Award },
};

function StatTile({ label, value }: { label: string; value: number | string }) {
    return (
        <div className="border-border bg-card rounded-xl border p-4">
            <p className="text-muted-foreground text-xs font-medium tracking-[0.15em] uppercase">{label}</p>
            <p className="text-display mt-2 text-3xl">{value}</p>
        </div>
    );
}

export default function MemberProfile({ profile }: { profile: PlayerProfile }) {
    const getInitials = useInitials();
    const since = profile.memberSince ? new Date(profile.memberSince).toLocaleDateString(undefined, { month: 'long', year: 'numeric' }) : null;

    return (
        <ClubLayout title={profile.name}>
            <div className="space-y-8">
                <Link
                    href={route('membership.members.index')}
                    className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-xs"
                >
                    <ArrowLeft className="size-3.5" /> Members
                </Link>

                {/* Identity */}
                <header className="flex flex-wrap items-center gap-4">
                    <span className="bg-muted flex size-16 items-center justify-center rounded-full text-xl font-semibold">
                        {getInitials(profile.name)}
                    </span>
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">{profile.name}</h1>
                        <div className="text-muted-foreground flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            {profile.roles.map((role) => (
                                <Badge key={role} variant="outline">
                                    {role}
                                </Badge>
                            ))}
                            {since && <span>Member since {since}</span>}
                        </div>
                    </div>
                </header>

                {/* Competitive record */}
                <section className="space-y-3">
                    <h2 className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Competitive record</h2>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <StatTile label="Played" value={profile.record.played} />
                        <StatTile label="Won" value={profile.record.won} />
                        <StatTile label="Lost" value={profile.record.lost} />
                        <StatTile label="Win %" value={`${profile.record.winPct}%`} />
                        <StatTile label="Titles" value={profile.record.titles} />
                        <StatTile label="Finals" value={profile.record.finals} />
                    </div>
                </section>

                {/* Trophy case */}
                <section className="space-y-3">
                    <h2 className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Trophy case</h2>
                    {profile.trophies.length === 0 ? (
                        <p className="text-muted-foreground text-sm">No podium finishes yet — results are recorded by the tournament organisers.</p>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {profile.trophies.map((t, i) => {
                                const meta = PLACEMENT[t.placement];
                                const Icon = meta.icon;
                                return (
                                    <Card key={`${t.tournamentId}-${t.category}-${i}`}>
                                        <CardContent className="flex items-center gap-4 pt-6">
                                            <span className="bg-muted flex size-12 shrink-0 items-center justify-center rounded-full">
                                                <Icon className="size-6" />
                                            </span>
                                            <div className="min-w-0">
                                                <p className="font-medium">{meta.label}</p>
                                                <p className="text-muted-foreground truncate text-sm">
                                                    {t.category}
                                                    {t.tournament && <> · {t.tournament}</>}
                                                </p>
                                            </div>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    )}
                </section>

                {/* Achievement badges */}
                {profile.badges.length > 0 && (
                    <section className="space-y-3">
                        <h2 className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Achievements</h2>
                        <div className="flex flex-wrap gap-2">
                            {profile.badges.map((b) => (
                                <Badge key={b.key} variant="secondary" className="gap-1.5 px-3 py-1">
                                    <Trophy className="size-3.5" /> {b.label}
                                </Badge>
                            ))}
                        </div>
                    </section>
                )}

                <div className="grid gap-8 lg:grid-cols-3">
                    {/* Recent results */}
                    <section className="space-y-3 lg:col-span-2">
                        <h2 className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Recent results</h2>
                        {profile.recentResults.length === 0 ? (
                            <p className="text-muted-foreground text-sm">No matches recorded yet.</p>
                        ) : (
                            <ul className="divide-border border-border bg-card divide-y rounded-xl border">
                                {profile.recentResults.map((r) => (
                                    <li key={r.id} className="flex items-center justify-between gap-4 p-4">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {r.won ? 'Beat' : 'Lost to'} {r.opponent ?? 'an opponent'}
                                            </p>
                                            <p className="text-muted-foreground truncate text-xs">
                                                {r.round} · {r.category}
                                                {r.tournament && <> · {r.tournament}</>}
                                            </p>
                                        </div>
                                        <span className="flex items-center gap-3">
                                            {r.score && <span className="text-display text-muted-foreground text-sm">{r.score}</span>}
                                            <Badge variant={r.won ? 'default' : 'outline'}>{r.won ? 'W' : 'L'}</Badge>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    {/* Club activity */}
                    <section className="space-y-3">
                        <h2 className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Club activity</h2>
                        <div className="border-border bg-card divide-border divide-y rounded-xl border">
                            <div className="flex items-center justify-between p-4">
                                <span className="text-sm">Tournaments entered</span>
                                <span className="text-display">{profile.activity.tournaments}</span>
                            </div>
                            <div className="flex items-center justify-between p-4">
                                <span className="text-sm">Teams</span>
                                <span className="text-display">{profile.activity.teams}</span>
                            </div>
                            <div className="flex items-center justify-between p-4">
                                <span className="text-sm">Bookings</span>
                                <span className="text-display">{profile.activity.bookings}</span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </ClubLayout>
    );
}
