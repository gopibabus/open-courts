import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface FormatOption {
    value: string;
    label: string;
}

interface CreateTournamentProps {
    formats: FormatOption[];
}

interface TournamentForm {
    name: string;
    format: string;
    starts_on: string;
    ends_on: string;
    [key: string]: string;
}

export default function CreateTournament({ formats }: CreateTournamentProps) {
    const { data, setData, post, processing, errors } = useForm<TournamentForm>({
        name: '',
        format: formats[0]?.value ?? 'single_elimination',
        starts_on: '',
        ends_on: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tournaments.store'));
    };

    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title="New tournament" />

            <div className="mx-auto max-w-xl space-y-8 p-8">
                <header className="space-y-2">
                    <Link
                        href={route('tournaments.index')}
                        className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-3.5" /> Tournaments
                    </Link>
                    <h1 className="text-2xl font-semibold tracking-tight">New tournament</h1>
                    <p className="text-sm text-muted-foreground">
                        Create the event, then add categories and open registration from its page.
                    </p>
                </header>

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            autoFocus
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Spring Club Championships"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="format">Format</Label>
                        <Select value={data.format} onValueChange={(v) => setData('format', v)}>
                            <SelectTrigger id="format">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {formats.map((f) => (
                                    <SelectItem key={f.value} value={f.value}>
                                        {f.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.format} />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="starts_on">Starts on</Label>
                            <Input
                                id="starts_on"
                                type="date"
                                value={data.starts_on}
                                onChange={(e) => setData('starts_on', e.target.value)}
                            />
                            <InputError message={errors.starts_on} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="ends_on">Ends on</Label>
                            <Input
                                id="ends_on"
                                type="date"
                                value={data.ends_on}
                                onChange={(e) => setData('ends_on', e.target.value)}
                            />
                            <InputError message={errors.ends_on} />
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Create tournament
                        </Button>
                        <Button asChild variant="ghost">
                            <Link href={route('tournaments.index')}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
