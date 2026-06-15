import { Logo } from '@/components/logo';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, Check, ChevronsUpDown, LayoutGrid, MapPin, Trophy, Users } from 'lucide-react';

/** Build the absolute URL of a club's subdomain, preserving the current scheme + port. */
function clubHref(slug: string): string {
    if (typeof window === 'undefined') return '/';
    const { protocol, host } = window.location;
    const central = host.replace(/^[^.]+\./, ''); // strip the current club's subdomain
    return `${protocol}//${slug}.${central}/`;
}

/**
 * The club workspace sidebar — brand at the top, the club's sections in the middle, and
 * a club switcher at the bottom. Teams are not a top-level section: they live inside a
 * tournament. The switcher lists every club the signed-in user belongs to.
 */
export function ClubSidebar() {
    const page = usePage<SharedData>();
    const club = page.props.club;
    const clubs = page.props.auth?.clubs ?? [];
    const canSwitch = clubs.length > 1;

    const nav = [
        { title: 'Dashboard', href: route('tenant.dashboard'), match: '/', icon: LayoutGrid },
        { title: 'Bookings', href: route('bookings.index'), match: '/bookings', icon: CalendarDays },
        { title: 'Courts', href: route('courts.index'), match: '/courts', icon: MapPin },
        { title: 'Tournaments', href: route('tournaments.index'), match: '/tournaments', icon: Trophy },
        { title: 'Members', href: route('membership.members.index'), match: '/members', icon: Users },
    ];

    const isActive = (match: string) => (match === '/' ? page.url === '/' : page.url.startsWith(match));
    const initial = (club?.name ?? 'C').charAt(0).toUpperCase();

    const identity = (
        <>
            <span className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md text-sm font-semibold">
                {initial}
            </span>
            <span className="grid flex-1 text-left leading-tight">
                <span className="truncate font-medium">{club?.name ?? 'Open Courts'}</span>
                <span className="text-muted-foreground truncate text-xs">{canSwitch ? 'Switch workspace' : 'Workspace'}</span>
            </span>
            {canSwitch && <ChevronsUpDown className="text-muted-foreground ml-auto size-4" />}
        </>
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="group-data-[collapsible=icon]:!p-0">
                            <Link href={route('tenant.dashboard')} aria-label={club?.name ?? 'Open Courts'}>
                                {/* Expanded: the brand wordmark. The source PNGs are padded squares, so
                                    object-cover crops to the centre band where the wordmark sits — otherwise
                                    height-sizing a 500×500 canvas shrinks the actual mark to a few pixels. */}
                                <Logo className="h-9 w-full rounded-sm object-cover object-center group-data-[collapsible=icon]:hidden" />
                                {/* Collapsed icon rail: a compact square mark. */}
                                <span className="bg-sidebar-primary text-sidebar-primary-foreground hidden aspect-square size-8 items-center justify-center rounded-md text-sm font-semibold group-data-[collapsible=icon]:flex">
                                    {initial}
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup className="px-2 py-0">
                    <SidebarMenu>
                        {nav.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton asChild isActive={isActive(item.match)} tooltip={item.title}>
                                    <Link href={item.href}>
                                        <item.icon />
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        {canSwitch ? (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <SidebarMenuButton size="lg" tooltip={club?.name ?? 'Open Courts'}>
                                        {identity}
                                    </SidebarMenuButton>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent side="top" align="start" className="min-w-56">
                                    <DropdownMenuLabel className="text-muted-foreground text-xs">Your clubs</DropdownMenuLabel>
                                    {clubs.map((c) => (
                                        <DropdownMenuItem key={c.id} asChild>
                                            <a href={clubHref(c.slug)} className="flex items-center justify-between gap-2">
                                                <span className="truncate">{c.name}</span>
                                                {c.slug === club?.slug && <Check className="size-4" />}
                                            </a>
                                        </DropdownMenuItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        ) : (
                            <SidebarMenuButton size="lg" tooltip={club?.name ?? 'Open Courts'}>
                                {identity}
                            </SidebarMenuButton>
                        )}
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
        </Sidebar>
    );
}
