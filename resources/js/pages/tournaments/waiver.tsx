import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2 } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ClubLayout from '@/layouts/club-layout';

interface WaiverProps {
    tournament: { id: number; name: string };
    memberName: string;
    waiverText: string[];
    signed: { signature: string; signedAt: string } | null;
}

interface WaiverForm {
    agree: boolean;
    signature: string;
    [key: string]: string | boolean;
}

export default function WaiverPage({ tournament, memberName, waiverText, signed }: WaiverProps) {
    const { data, setData, post, processing, errors } = useForm<WaiverForm>({
        agree: false,
        signature: signed?.signature ?? memberName,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tournaments.waiver.store', tournament.id));
    };

    return (
        <ClubLayout title="Waiver">
            <div className="mx-auto max-w-2xl space-y-6">
                <Link
                    href={route('tournaments.show', tournament.id)}
                    className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-xs"
                >
                    <ArrowLeft className="size-3.5" /> {tournament.name}
                </Link>

                <header className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">Player waiver</h1>
                    <p className="text-muted-foreground text-sm">Please read and sign to compete in {tournament.name}.</p>
                </header>

                <div className="border-border bg-card text-muted-foreground space-y-3 rounded-xl border p-5 text-sm leading-relaxed">
                    {waiverText.map((clause, i) => (
                        <p key={i}>{clause}</p>
                    ))}
                </div>

                {signed ? (
                    <div className="border-border bg-card flex items-center gap-3 rounded-xl border p-4">
                        <CheckCircle2 className="size-5 shrink-0" />
                        <div>
                            <p className="text-sm font-medium">Signed by {signed.signature}</p>
                            <p className="text-muted-foreground text-xs">on {new Date(signed.signedAt).toLocaleString()}</p>
                        </div>
                    </div>
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        <div className="flex items-start gap-2">
                            <Checkbox id="agree" checked={data.agree} onCheckedChange={(c) => setData('agree', c === true)} className="mt-0.5" />
                            <Label htmlFor="agree" className="text-sm leading-snug font-normal">
                                I have read and agree to the waiver above, and I’m signing on my own behalf.
                            </Label>
                        </div>
                        <InputError message={errors.agree} />

                        <div className="grid max-w-sm gap-2">
                            <Label htmlFor="signature">Signature — type your full name</Label>
                            <Input
                                id="signature"
                                value={data.signature}
                                onChange={(e) => setData('signature', e.target.value)}
                                placeholder="Your full name"
                                maxLength={120}
                            />
                            <InputError message={errors.signature} />
                        </div>

                        <Button type="submit" disabled={processing || !data.agree || !data.signature.trim()}>
                            Sign waiver
                        </Button>
                    </form>
                )}
            </div>
        </ClubLayout>
    );
}
