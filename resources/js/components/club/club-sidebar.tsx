import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, ChevronsUpDown, LayoutGrid, MapPin, Trophy, Users, UsersRound } from 'lucide-react';

/**
 * The club workspace sidebar — brand at the top, the club's main sections in the
 * middle, and the current club identity at the bottom. Nav targets are tenant
 * routes (resolved relative to the current club subdomain); active state is matched
 * against the current path so it works regardless of which club is active.
 */
export function ClubSidebar() {
    const page = usePage<SharedData>();
    const club = page.props.club;

    const nav = [
        { title: 'Dashboard', href: route('tenant.dashboard'), match: '/', icon: LayoutGrid },
        { title: 'Bookings', href: route('bookings.index'), match: '/bookings', icon: CalendarDays },
        { title: 'Courts', href: route('courts.index'), match: '/courts', icon: MapPin },
        { title: 'Tournaments', href: route('tournaments.index'), match: '/tournaments', icon: Trophy },
        { title: 'Teams', href: route('teams.index'), match: '/teams', icon: UsersRound },
        { title: 'Members', href: route('membership.members.index'), match: '/members', icon: Users },
    ];

    const isActive = (match: string) => (match === '/' ? page.url === '/' : page.url.startsWith(match));
    const initial = (club?.name ?? 'C').charAt(0).toUpperCase();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route('tenant.dashboard')}>
                                <span className="bg-primary text-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md">
                                    <span className="text-display text-sm leading-none">O</span>
                                </span>
                                <span className="text-display text-base leading-none">OpenTennis</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Club</SidebarGroupLabel>
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
                        <SidebarMenuButton size="lg" tooltip={club?.name ?? 'Club'}>
                            <span className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md text-sm font-semibold">
                                {initial}
                            </span>
                            <span className="grid flex-1 text-left leading-tight">
                                <span className="truncate font-medium">{club?.name ?? 'Club'}</span>
                                <span className="text-muted-foreground truncate text-xs">Club workspace</span>
                            </span>
                            <ChevronsUpDown className="text-muted-foreground ml-auto size-4" />
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
        </Sidebar>
    );
}
