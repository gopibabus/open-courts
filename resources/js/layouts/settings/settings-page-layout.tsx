import AppLayout from '@/layouts/app-layout';
import ClubLayout from '@/layouts/club-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

/**
 * Settings pages live on a UNIVERSAL route (auth group, not domain-constrained), so the
 * same page renders on both the central app and a club subdomain. To keep the shell
 * identical when a member moves between the dashboard and /settings/* on a club
 * subdomain — same sidebar, topbar and collapse behaviour — we wrap the settings body in
 * ClubLayout there (detected via the shared `club` prop) and fall back to the central
 * AppLayout otherwise. The inner SettingsLayout (heading + section nav) is shared.
 */
export default function SettingsPageLayout({
    title,
    breadcrumbs,
    children,
}: {
    title: string;
    breadcrumbs: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const { club } = usePage<SharedData>().props;

    if (club) {
        // ClubLayout sets the document <title> itself.
        return (
            <ClubLayout title={title}>
                <SettingsLayout>{children}</SettingsLayout>
            </ClubLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <SettingsLayout>{children}</SettingsLayout>
        </AppLayout>
    );
}
