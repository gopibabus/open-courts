import { Transition } from '@headlessui/react';
import { Link, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowLeft, ArrowUp, Plus, RotateCcw, Trash2 } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import ClubLayout from '@/layouts/club-layout';

interface WaiverTemplateProps {
    clauses: string[];
    defaults: string[];
    isCustomised: boolean;
}

interface WaiverTemplateForm {
    clauses: string[];
    [key: string]: string[];
}

export default function WaiverTemplatePage({ clauses, defaults, isCustomised }: WaiverTemplateProps) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm<WaiverTemplateForm>({
        // Ensure there is always at least one (empty) row to edit.
        clauses: clauses.length > 0 ? clauses : [''],
    });

    const setClauses = (next: string[]) => setData('clauses', next);

    const updateClause = (index: number, value: string) => {
        setClauses(data.clauses.map((c, i) => (i === index ? value : c)));
    };

    const addClause = () => setClauses([...data.clauses, '']);

    const removeClause = (index: number) => {
        const next = data.clauses.filter((_, i) => i !== index);
        setClauses(next.length > 0 ? next : ['']);
    };

    const moveClause = (index: number, direction: -1 | 1) => {
        const target = index + direction;
        if (target < 0 || target >= data.clauses.length) return;
        const next = [...data.clauses];
        [next[index], next[target]] = [next[target], next[index]];
        setClauses(next);
    };

    const resetToDefault = () => setClauses(defaults);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('tournaments.waiver-template.update'), { preserveScroll: true });
    };

    return (
        <ClubLayout title="Waiver template">
            <div className="mx-auto max-w-2xl space-y-6">
                <Link
                    href={route('tournaments.index')}
                    className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-xs"
                >
                    <ArrowLeft className="size-3.5" /> Tournaments
                </Link>

                <header className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">Waiver template</h1>
                    <p className="text-muted-foreground text-sm">
                        The clauses every player agrees to before competing. {isCustomised ? 'Customised for your club.' : 'Currently using the default clauses.'}
                    </p>
                    <p className="text-muted-foreground text-xs">
                        Tip: write <code className="bg-muted rounded px-1 py-0.5">{'{tournament}'}</code> to insert the tournament’s name. Editing
                        this template never changes waivers players have already signed.
                    </p>
                </header>

                <form onSubmit={submit} className="space-y-4">
                    <InputError message={errors.clauses} />

                    <ol className="space-y-3">
                        {data.clauses.map((clause, index) => (
                            <li key={index} className="border-border bg-card flex gap-3 rounded-xl border p-3">
                                <span className="text-muted-foreground text-display mt-2 w-5 shrink-0 text-right text-sm">{index + 1}</span>
                                <div className="flex-1 space-y-1">
                                    <Textarea
                                        value={clause}
                                        onChange={(e) => updateClause(index, e.target.value)}
                                        rows={3}
                                        maxLength={2000}
                                        placeholder="Clause text…"
                                        aria-label={`Clause ${index + 1}`}
                                    />
                                    <InputError message={errors[`clauses.${index}`]} />
                                </div>
                                <div className="flex shrink-0 flex-col gap-1">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="size-7"
                                        onClick={() => moveClause(index, -1)}
                                        disabled={index === 0}
                                        aria-label={`Move clause ${index + 1} up`}
                                    >
                                        <ArrowUp className="size-3.5" />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="size-7"
                                        onClick={() => moveClause(index, 1)}
                                        disabled={index === data.clauses.length - 1}
                                        aria-label={`Move clause ${index + 1} down`}
                                    >
                                        <ArrowDown className="size-3.5" />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="size-7"
                                        onClick={() => removeClause(index)}
                                        aria-label={`Remove clause ${index + 1}`}
                                    >
                                        <Trash2 className="size-3.5" />
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ol>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button type="button" variant="outline" size="sm" onClick={addClause}>
                            <Plus className="size-3.5" /> Add clause
                        </Button>
                        <Button type="button" variant="ghost" size="sm" onClick={resetToDefault}>
                            <RotateCcw className="size-3.5" /> Reset to default
                        </Button>
                    </div>

                    <div className="flex items-center gap-4 pt-2">
                        <Button type="submit" disabled={processing}>
                            Save template
                        </Button>
                        <Transition
                            show={recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-muted-foreground text-sm">Saved</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </ClubLayout>
    );
}
