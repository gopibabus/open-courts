import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Logo } from './logo';

export default function AppLogo() {
    const { branding } = usePage<SharedData>().props;

    return (
        <>
            <Logo className="size-8 shrink-0 object-contain" />
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold">{branding?.name ?? 'Open Courts'}</span>
            </div>
        </>
    );
}
