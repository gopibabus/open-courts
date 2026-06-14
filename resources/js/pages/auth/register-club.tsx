import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface RegisterClubForm {
    club_name: string;
    slug: string;
    owner_name: string;
    owner_email: string;
    password: string;
    password_confirmation: string;
}

interface RegisterClubProps {
    centralDomain: string;
}

// Live-derive a DNS-safe subdomain suggestion from the club name.
const slugify = (value: string) =>
    value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 63);

export default function RegisterClub({ centralDomain }: RegisterClubProps) {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterClubForm>({
        club_name: '',
        slug: '',
        owner_name: '',
        owner_email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register-club.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout title="Start your club" description="Create your club workspace and owner account">
            <Head title="Register a club" />

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="club_name">Club name</Label>
                        <Input
                            id="club_name"
                            required
                            autoFocus
                            tabIndex={1}
                            value={data.club_name}
                            onChange={(e) => {
                                const name = e.target.value;
                                setData((prev) => ({
                                    ...prev,
                                    club_name: name,
                                    // keep slug in sync until the user edits it directly
                                    slug: prev.slug === slugify(prev.club_name) ? slugify(name) : prev.slug,
                                }));
                            }}
                            placeholder="Smash Tennis Club"
                        />
                        <InputError message={errors.club_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">Club address</Label>
                        <div className="flex items-center gap-1.5">
                            <Input
                                id="slug"
                                required
                                tabIndex={2}
                                value={data.slug}
                                onChange={(e) => setData('slug', slugify(e.target.value))}
                                placeholder="smashclub"
                                className="max-w-[12rem]"
                                aria-describedby="slug-preview"
                            />
                            <span id="slug-preview" className="text-sm text-muted-foreground">
                                .{centralDomain}
                            </span>
                        </div>
                        <InputError message={errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="owner_name">Your name</Label>
                        <Input
                            id="owner_name"
                            required
                            tabIndex={3}
                            autoComplete="name"
                            value={data.owner_name}
                            onChange={(e) => setData('owner_name', e.target.value)}
                            placeholder="Sasha Owner"
                        />
                        <InputError message={errors.owner_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="owner_email">Email</Label>
                        <Input
                            id="owner_email"
                            type="email"
                            required
                            tabIndex={4}
                            autoComplete="email"
                            value={data.owner_email}
                            onChange={(e) => setData('owner_email', e.target.value)}
                            placeholder="you@club.com"
                        />
                        <InputError message={errors.owner_email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            tabIndex={5}
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="••••••••"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            tabIndex={6}
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            placeholder="••••••••"
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>

                    <Button type="submit" className="mt-2 w-full" tabIndex={7} disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Create club
                    </Button>
                </div>

                <div className="text-center text-sm text-muted-foreground">
                    Already have a club?{' '}
                    <TextLink href={route('login')} tabIndex={8}>
                        Log in
                    </TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}
