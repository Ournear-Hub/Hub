import type { PropsWithChildren } from 'react';

export type BreadcrumbItem = {
    label: string;
    href?: string;
};

export type LayoutProps = PropsWithChildren<{
    title?: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
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
    children?: AdminNavItem[];
};

export type NavSection = {
    id: string;
    label: string;
    items: AdminNavItem[];
};
