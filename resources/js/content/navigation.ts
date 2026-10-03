import type { NavItem } from '@/types/layout';

/** Locked public primary navigation (single source). */
export const primaryNav: NavItem[] = [
    { label: 'Jobs', href: '/jobs' },
    { label: 'Companies', href: '/companies' },
    { label: 'Hire', href: '/hire' },
    { label: 'Talent Pool', href: '/talent-pool' },
    { label: 'Staffing', href: '/staffing' },
    { label: 'About', href: '/about' },
];

export function isNavActive(url: string, href: string): boolean {
    const path = url.split('#')[0] ?? url;

    if (href === '/') {
        return path === '/';
    }

    if (href === '/jobs') {
        return path === '/jobs' || path.startsWith('/jobs/');
    }

    if (href === '/companies' || href === '/hire' || href === '/talent-pool' || href === '/staffing' || href === '/about') {
        return path === href || path.startsWith(`${href}/`);
    }

    return path === href || path.startsWith(`${href}/`);
}
