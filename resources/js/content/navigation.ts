import type { NavItem } from '@/types/layout';

/** Locked public primary navigation (single source). */
export const primaryNav: NavItem[] = [
    { label: 'Jobs', href: '/jobs' },
    { label: 'Hiring', href: '/hire' },
    { label: 'Talent Pool', href: '/talent-pool' },
    { label: 'Staffing', href: '/staffing' },
    { label: 'Development Programme', href: '/development-programme' },
];

export function isNavActive(url: string, href: string): boolean {
    const withoutHash = url.split('#')[0] ?? url;
    const path = withoutHash.split('?')[0] ?? withoutHash;

    if (href === '/') {
        return path === '/';
    }

    if (href === '/jobs') {
        return path === '/jobs' || path.startsWith('/jobs/');
    }

    if (href === '/hire' || href === '/talent-pool' || href === '/staffing' || href === '/about') {
        return path === href || path.startsWith(`${href}/`);
    }

    return path === href || path.startsWith(`${href}/`);
}
