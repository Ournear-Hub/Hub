import type { PropsWithChildren } from 'react';

export type LayoutProps = PropsWithChildren<{
    title?: string;
    description?: string;
}>;

export type NavItem = {
    label: string;
    href: string;
    children?: NavItem[];
};

export type AdminNavItem = {
    label: string;
    href?: string;
    comingSoon?: boolean;
    permission?: string;
    /** V1.1 core modules stay in the admin sidebar even when staff RBAC is empty or incomplete. */
    core?: boolean;
};

export type NavSection = {
    id: string;
    label: string;
    items: AdminNavItem[];
};
