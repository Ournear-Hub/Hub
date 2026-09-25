import type { NavItem } from '@/types/layout';

export const primaryNav: NavItem[] = [
    { label: 'Home', href: '/' },
    { label: 'Jobs', href: '/jobs' },
    { label: 'Freelancers', href: '/freelancers' },
    { label: 'About Us', href: '/about' },
    {
        label: 'Services',
        href: '/services',
        children: [
            { label: 'Manpower Supply', href: '/services#manpower-supply' },
            { label: 'Training & Development', href: '/services#training-development' },
            { label: 'Project Support & Management', href: '/services#project-support-management' },
        ],
    },
    { label: 'For Employers', href: '/for-employers' },
    { label: 'Contact Us', href: '/contact' },
];

export function isNavActive(url: string, href: string): boolean {
    const path = url.split('#')[0] ?? url;

    if (href === '/') {
        return path === '/';
    }

    if (href === '/jobs') {
        return path === '/jobs' || path.startsWith('/jobs/');
    }

    if (href === '/freelancers') {
        return path === '/freelancers' || path.startsWith('/freelancers/');
    }

    return path === href || path.startsWith(`${href}/`);
}
