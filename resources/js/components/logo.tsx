import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/**
 * The app logo image, driven by config/branding.php (shared as the `branding`
 * Inertia prop). By default it swaps the light/dark artwork by theme; pass
 * theme="dark" to force the dark-background logo (e.g. on an inverted band) or
 * theme="light" for the reverse. Replace the files in public/ to re-skin the
 * whole app from one place.
 */
export function Logo({ className, theme = 'auto' }: { className?: string; theme?: 'auto' | 'light' | 'dark' }) {
    const { branding } = usePage<SharedData>().props;
    const name = branding?.name ?? 'Open Courts';
    const light = `/${branding?.logo ?? 'logo1.png'}`;
    const dark = `/${branding?.logo_dark ?? 'logo2.png'}`;

    if (theme === 'light') return <img src={light} alt={name} className={className} />;
    if (theme === 'dark') return <img src={dark} alt={name} className={className} />;

    return (
        <>
            <img src={light} alt={name} className={cn(className, 'dark:hidden')} />
            <img src={dark} alt={name} className={cn('hidden dark:block', className)} />
        </>
    );
}
