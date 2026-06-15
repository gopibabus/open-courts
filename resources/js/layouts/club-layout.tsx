import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { ClubSidebar } from '@/components/club/club-sidebar';
import { CommandPalette } from '@/components/club/command-palette';
import { ThemeToggle } from '@/components/theme-toggle';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronDown, CircleHelp } from 'lucide-react';

/**
 * The shared club workspace shell: collapsible sidebar + sticky topbar, wrapping
 * every authenticated club page so navigation and chrome are consistent. Pass a
 * `title` for the topbar heading + the document title.
 */
export default function ClubLayout({ title, children }: { title?: string; children: React.ReactNode }) {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();

    return (
        <AppShell variant="sidebar">
            {title ? <Head title={title} /> : null}
            <ClubSidebar />
            <AppContent variant="sidebar">
                <header className="border-border bg-background/80 sticky top-0 z-10 flex h-16 shrink-0 items-center gap-3 border-b px-4 backdrop-blur md:px-6">
                    <SidebarTrigger className="-ml-1" />

                    {/* Cmd/Ctrl+K command palette — navigates to any club feature.
                        The page title lives in each page's own content <h1>, not here (one h1 per page). */}
                    <CommandPalette />

                    <div className="ml-auto flex items-center gap-2">
                        <ThemeToggle />
                        <Link
                            href={route('help.index')}
                            className="text-muted-foreground hover:text-foreground hidden items-center gap-1.5 rounded-md px-2 py-1.5 text-sm transition-colors sm:flex"
                        >
                            <CircleHelp className="size-4" /> Help
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button className="hover:bg-accent focus-visible:ring-ring flex items-center gap-2 rounded-md px-1.5 py-1 text-sm transition-colors focus-visible:ring-2 focus-visible:outline-none">
                                    <span className="bg-muted flex size-7 items-center justify-center rounded-full text-xs font-medium">
                                        {getInitials(auth.user.name)}
                                    </span>
                                    <span className="hidden font-medium sm:inline">{auth.user.name}</span>
                                    <ChevronDown className="text-muted-foreground size-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </header>

                <main className="flex-1 p-4 md:p-6">{children}</main>
            </AppContent>
        </AppShell>
    );
}
