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
