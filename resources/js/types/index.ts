import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface Club {
    id: string;
    name: string;
    slug: string;
}

/** App branding / metadata from config/branding.php, shared on every request. */
export interface Branding {
    name: string;
    tagline: string;
    description: string;
    logo: string;
    logo_dark: string;
    favicon: string;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    /** The active club, shared on club-subdomain requests; null on the central domain. */
    club?: Club | null;
    /** App branding / metadata from config/branding.php. */
    branding?: Branding | null;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    /** Platform super-admin flag. Always shared as a boolean; true only for real admins. */
    is_platform_admin: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
