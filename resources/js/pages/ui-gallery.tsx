import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { type Appearance, useAppearance } from '@/hooks/use-appearance';
import { Head } from '@inertiajs/react';
import { type ReactNode } from 'react';

const NEUTRALS: { name: string; className: string }[] = [
    { name: 'background', className: 'bg-background' },
    { name: 'card', className: 'bg-card' },
    { name: 'muted', className: 'bg-muted' },
    { name: 'accent', className: 'bg-accent' },
    { name: 'secondary', className: 'bg-secondary' },
    { name: 'border', className: 'bg-border' },
    { name: 'primary', className: 'bg-primary' },
    { name: 'foreground', className: 'bg-foreground' },
];

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-4">
            <h2 className="text-xs font-medium tracking-[0.2em] text-muted-foreground uppercase">{title}</h2>
            {children}
        </section>
    );
}

export default function UiGallery() {
    const { appearance, updateAppearance } = useAppearance();
    const modes: Appearance[] = ['light', 'dark', 'system'];

    return (
        <>
            <Head title="Design System" />

            <div className="min-h-screen bg-background text-foreground">
                {/* Top bar */}
                <header className="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/80 px-6 py-4 backdrop-blur">
                    <div className="flex items-baseline gap-3">
                        <span className="text-display text-2xl">OPEN·TENNIS</span>
                        <span className="text-xs text-muted-foreground">design system</span>
                    </div>
                    <div className="flex items-center gap-1 rounded-md border border-border p-0.5">
                        {modes.map((mode) => (
                            <button
                                key={mode}
                                onClick={() => updateAppearance(mode)}
                                className={`rounded px-2.5 py-1 text-xs capitalize transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none ${
                                    appearance === mode ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                {mode}
                            </button>
                        ))}
                    </div>
                </header>

                <main className="mx-auto max-w-4xl space-y-12 px-6 py-12">
                    <div className="space-y-2">
                        <h1 className="text-3xl font-semibold tracking-tight">Monochrome, mono-typed.</h1>
                        <p className="max-w-prose text-sm text-muted-foreground">
                            Black & white, JetBrains Mono throughout, with a Doto dot-matrix display face reserved for
                            highlight numerals — scores, court numbers, countdowns. Color appears only to signal state.
                        </p>
                    </div>

                    <Section title="Neutral ramp">
                        <div className="grid grid-cols-4 gap-3 sm:grid-cols-8">
                            {NEUTRALS.map((t) => (
                                <div key={t.name} className="space-y-1.5">
                                    <div className={`aspect-square w-full rounded-md border border-border ${t.className}`} />
                                    <div className="text-[11px] text-muted-foreground">{t.name}</div>
                                </div>
                            ))}
                        </div>
                    </Section>

                    <Section title="Dot-matrix display">
                        <Card>
                            <CardContent className="flex flex-wrap items-end justify-between gap-8 pt-6">
                                <div>
                                    <div className="text-xs text-muted-foreground">Final · Centre Court</div>
                                    <div className="text-display mt-1 text-6xl leading-none">6—4 7—5</div>
                                </div>
                                <div className="text-right">
                                    <div className="text-xs text-muted-foreground">Court</div>
                                    <div className="text-display text-6xl leading-none">01</div>
                                </div>
                                <div className="text-right">
                                    <div className="text-xs text-muted-foreground">Starts in</div>
                                    <div className="text-display text-6xl leading-none">12:30</div>
                                </div>
                            </CardContent>
                        </Card>
                    </Section>

                    <Section title="Type scale (JetBrains Mono)">
                        <div className="space-y-1.5">
                            <p className="text-lg">The quick brown fox — 18 / lg</p>
                            <p className="text-base">The quick brown fox — 16 / base</p>
                            <p className="text-sm">The quick brown fox — 14 / sm</p>
                            <p className="text-xs text-muted-foreground">The quick brown fox — 12 / xs · muted</p>
                        </div>
                    </Section>

                    <Section title="Buttons">
                        <div className="flex flex-wrap gap-3">
                            <Button>Primary</Button>
                            <Button variant="secondary">Secondary</Button>
                            <Button variant="outline">Outline</Button>
                            <Button variant="ghost">Ghost</Button>
                            <Button variant="destructive">Destructive</Button>
                            <Button variant="link">Link</Button>
                            <Button disabled>Disabled</Button>
                        </div>
                    </Section>

                    <Section title="Inputs">
                        <div className="grid max-w-md gap-4">
                            <div className="grid gap-1.5">
                                <Label htmlFor="club">Club name</Label>
                                <Input id="club" placeholder="Smash Tennis Club" />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" aria-invalid placeholder="invalid@" />
                                <p className="text-xs text-destructive">Enter a valid email address.</p>
                            </div>
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox defaultChecked /> Accept the club terms
                            </label>
                        </div>
                    </Section>

                    <Section title="Status">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge>reserved</Badge>
                            <Badge variant="secondary">draft</Badge>
                            <Badge variant="outline">member</Badge>
                            <Badge variant="destructive">cancelled</Badge>
                        </div>
                        <Alert variant="destructive">
                            <AlertTitle>Court unavailable</AlertTitle>
                            <AlertDescription>That slot was just booked by another member. Pick another time.</AlertDescription>
                        </Alert>
                    </Section>

                    <Section title="Composition · court card">
                        <Card className="max-w-sm">
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle className="text-base">Centre Court</CardTitle>
                                <Badge variant="outline">hard</Badge>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <Separator />
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">Next free</span>
                                    <span className="text-display text-lg">14:00</span>
                                </div>
                            </CardContent>
                            <CardFooter>
                                <Button className="w-full">Book</Button>
                            </CardFooter>
                        </Card>
                    </Section>
                </main>
            </div>
        </>
    );
}
