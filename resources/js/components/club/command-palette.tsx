import { CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { router } from '@inertiajs/react';
import { CalendarDays, CircleHelp, LayoutGrid, MapPin, Search, Settings, Trophy, Users, type LucideIcon } from 'lucide-react';
import * as React from 'react';

interface PaletteItem {
    title: string;
    href: string;
    icon: LucideIcon;
    keywords?: string;
}

interface PaletteGroup {
    heading: string;
    items: PaletteItem[];
}

/**
 * Cmd+K / Ctrl+K command palette for the club workspace. Built on shadcn's Command
 * (a `cmdk` wrapper). Selecting an entry navigates to that feature via Inertia. Rendered
 * in the club shell topbar, so it is available on the dashboard and every club page.
 */
export function CommandPalette() {
    const [open, setOpen] = React.useState(false);
    const [modKey, setModKey] = React.useState('⌘');

    React.useEffect(() => {
        // Show "Ctrl" off macOS; default to "⌘" to avoid an SSR/first-paint label flip on Mac.
        const isMac = /Mac|iPhone|iPad|iPod/.test(navigator.platform) || /Mac/.test(navigator.userAgent);
        if (!isMac) setModKey('Ctrl');

        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key.toLowerCase() === 'k' && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                setOpen((prev) => !prev);
            }
        };

        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, []);

    const go = (href: string) => {
        setOpen(false);
        router.visit(href);
    };

    // Member-accessible destinations only, so nothing in the palette dead-ends at a 403.
    const groups: PaletteGroup[] = [
        {
            heading: 'Navigate',
            items: [
                { title: 'Dashboard', href: route('tenant.dashboard'), icon: LayoutGrid, keywords: 'home overview' },
                { title: 'Bookings', href: route('bookings.index'), icon: CalendarDays, keywords: 'reservations court time' },
                { title: 'Courts', href: route('courts.index'), icon: MapPin, keywords: 'availability surface' },
                { title: 'Tournaments', href: route('tournaments.index'), icon: Trophy, keywords: 'brackets draws competitions waiver' },
                { title: 'Members', href: route('membership.members.index'), icon: Users, keywords: 'players directory profiles' },
            ],
        },
        {
            heading: 'More',
            items: [
                { title: 'Account settings', href: route('profile.edit'), icon: Settings, keywords: 'profile password appearance' },
                { title: 'Help & support', href: route('help.index'), icon: CircleHelp, keywords: 'contact request' },
            ],
        },
    ];

    return (
        <>
            {/* Topbar trigger (md+) — replaces the old static search placeholder. */}
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="border-border text-muted-foreground hover:bg-accent ml-2 hidden items-center gap-2 rounded-md border px-3 py-1.5 text-sm transition-colors md:flex lg:w-72"
            >
                <Search className="size-4" />
                <span className="flex-1 text-left">Search…</span>
                <kbd className="border-border rounded border px-1.5 text-[10px]">{modKey}K</kbd>
            </button>

            {/* Compact trigger (mobile) — the topbar bar is hidden below md. */}
            <button
                type="button"
                onClick={() => setOpen(true)}
                aria-label="Search"
                className="text-muted-foreground hover:bg-accent hover:text-foreground ml-2 flex size-9 items-center justify-center rounded-md transition-colors md:hidden"
            >
                <Search className="size-4" />
            </button>

            <CommandDialog open={open} onOpenChange={setOpen}>
                <CommandInput placeholder="Search for a page…" />
                <CommandList>
                    <CommandEmpty>No results found.</CommandEmpty>
                    {groups.map((group) => (
                        <CommandGroup key={group.heading} heading={group.heading}>
                            {group.items.map((item) => (
                                <CommandItem
                                    key={item.title}
                                    value={`${item.title} ${item.keywords ?? ''}`}
                                    onSelect={() => go(item.href)}
                                >
                                    <item.icon />
                                    <span>{item.title}</span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    ))}
                </CommandList>
            </CommandDialog>
        </>
    );
}
