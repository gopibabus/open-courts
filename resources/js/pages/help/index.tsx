import { useForm } from '@inertiajs/react';
import { CheckCircle2, LifeBuoy, Mail } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import ClubLayout from '@/layouts/club-layout';

interface HelpForm {
    category: string;
    subject: string;
    message: string;
    [key: string]: string;
}

const CATEGORY_LABELS: Record<string, string> = {
    booking: 'Court bookings',
    courts: 'Courts & availability',
    tournaments: 'Tournaments & teams',
    membership: 'Members & invitations',
    billing: 'Billing',
    other: 'Something else',
};

const FAQS: { q: string; a: string }[] = [
    {
        q: 'How do I book a court?',
        a: 'Open Bookings in the sidebar, choose a court and a day, then pick an open slot. Your reservation is confirmed instantly.',
    },
    {
        q: 'How do teams work?',
        a: 'Teams live inside a tournament. Create a tournament first, then add teams to it — a member can be on only one team per tournament.',
    },
    {
        q: 'How do I invite members?',
        a: 'Club admins can invite people from the Members page. Each invitee gets an email link to set up their account and join the club.',
    },
    {
        q: 'Can I belong to more than one club?',
        a: 'Yes. If you’re a member of several clubs, use the club switcher at the bottom of the sidebar to jump between them.',
    },
];

export default function HelpIndex({ categories }: { categories: string[] }) {
    const [sent, setSent] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<HelpForm>({
        category: categories[0] ?? 'other',
        subject: '',
        message: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('help.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset('subject', 'message');
                setSent(true);
            },
        });
    };

    return (
        <ClubLayout title="Help">
            <div className="space-y-8">
                <header className="space-y-1">
                    <p className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Support</p>
                    <h1 className="text-2xl font-semibold tracking-tight">Help &amp; support</h1>
                    <p className="text-muted-foreground text-sm">Browse the common questions, or send us a note — we’re happy to help.</p>
                </header>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Support form — the primary action */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <LifeBuoy className="size-4" /> Send us a request
                            </CardTitle>
                            <CardDescription>Tell us what’s going on and we’ll get back to you by email.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {sent && (
                                <div
                                    className="border-border bg-card text-foreground mb-6 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm"
                                    role="status"
                                >
                                    <CheckCircle2 className="size-4 shrink-0" />
                                    Thanks — we’ve received your request and will be in touch.
                                </div>
                            )}

                            <form onSubmit={submit} className="space-y-5" onChange={() => sent && setSent(false)}>
                                <div className="grid gap-2">
                                    <Label htmlFor="category">Topic</Label>
                                    <Select value={data.category} onValueChange={(v) => setData('category', v)}>
                                        <SelectTrigger id="category">
                                            <SelectValue placeholder="Select a topic" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((c) => (
                                                <SelectItem key={c} value={c}>
                                                    {CATEGORY_LABELS[c] ?? c}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.category} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="subject">Subject</Label>
                                    <Input
                                        id="subject"
                                        value={data.subject}
                                        onChange={(e) => setData('subject', e.target.value)}
                                        placeholder="A short summary"
                                        maxLength={150}
                                    />
                                    <InputError message={errors.subject} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="message">Message</Label>
                                    <Textarea
                                        id="message"
                                        value={data.message}
                                        onChange={(e) => setData('message', e.target.value)}
                                        placeholder="Describe what you need help with…"
                                        rows={6}
                                    />
                                    <InputError message={errors.message} />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    Send request
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {/* FAQ + direct contact */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Common questions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                {FAQS.map((faq) => (
                                    <div key={faq.q} className="space-y-1">
                                        <p className="text-sm font-medium">{faq.q}</p>
                                        <p className="text-muted-foreground text-sm leading-relaxed">{faq.a}</p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="flex items-start gap-3 pt-6">
                                <Mail className="text-muted-foreground mt-0.5 size-4 shrink-0" />
                                <p className="text-muted-foreground text-sm">
                                    Prefer email? Your request lands straight in our support inbox and we’ll reply to the address on your account.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </ClubLayout>
    );
}
