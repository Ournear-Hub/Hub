import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BrandMark from '@/Components/BrandMark';
import type { AdminNavItem, NavSection } from '@/types/layout';

const dashboardItem: AdminNavItem = { label: 'Dashboard', href: '/admin' };

const sections: NavSection[] = [
    {
        id: 'recruitment',
        label: 'Recruitment',
        items: [
            { label: 'Jobs', href: '/admin/jobs' },
            { label: 'Candidates', href: '/admin/candidates' },
            { label: 'Applications', href: '/admin/applications' },
            { label: 'Screening', comingSoon: true },
            { label: 'Shortlisted', comingSoon: true },
            { label: 'Employer Submissions', comingSoon: true },
            { label: 'Placements', comingSoon: true },
            { label: 'Joined / Employees', comingSoon: true },
            { label: 'Replacements', comingSoon: true },
        ],
    },
    {
        id: 'employers',
        label: 'Employers',
        items: [
            { label: 'Employers', comingSoon: true },
            { label: 'Employer Enquiries', href: '/admin/employer-enquiries' },
        ],
    },
    {
        id: 'finance',
        label: 'Finance',
        items: [{ label: 'Payments', comingSoon: true }],
    },
    {
        id: 'communication',
        label: 'Communication',
        items: [
            { label: 'Notifications', comingSoon: true },
            { label: 'Messages', comingSoon: true },
        ],
    },
    {
        id: 'users',
        label: 'Users & Security',
        items: [
            { label: 'Users', comingSoon: true },
            { label: 'Activity Logs', comingSoon: true },
            { label: 'Login Logs', comingSoon: true },
            { label: 'Approval Requests', comingSoon: true },
        ],
    },
    {
        id: 'website',
        label: 'Website',
        items: [
            { label: 'Content', href: '/admin/website-content' },
            { label: 'Media & Social', href: '/admin/media-social' },
            { label: 'Settings', href: '/admin/settings' },
        ],
    },
    {
        id: 'administration',
        label: 'Administration',
        items: [
            { label: 'Admin Users', comingSoon: true },
            { label: 'Roles & Permissions', comingSoon: true },
            { label: 'Master Data', href: '/admin/master-data' },
        ],
    },
];

type SidebarProps = {
    open: boolean;
    onClose: () => void;
};

function isActive(url: string, href: string): boolean {
    if (href === '/admin') {
        return url === '/admin' || url === '/admin/';
    }

    return url === href || url.startsWith(`${href}/`);
}

function sectionHasActive(url: string, items: AdminNavItem[]): boolean {
    return items.some((item) => item.href && isActive(url, item.href));
}

function NavLink({ item, url, onClose }: { item: AdminNavItem; url: string; onClose: () => void }) {
    if (item.comingSoon || !item.href) {
        return (
            <span
                className="flex items-center justify-between rounded-md px-3 py-2 text-sm text-white/45"
                title="Coming in a later V1.1 phase"
                aria-disabled="true"
            >
                <span>{item.label}</span>
                <span className="text-[10px] uppercase tracking-wide text-white/35">Soon</span>
            </span>
        );
    }

    const active = isActive(url, item.href);

    return (
        <Link
            href={item.href}
            onClick={onClose}
            className={`block rounded-md px-3 py-2 text-sm font-medium ${
                active ? 'bg-white/15 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white'
            }`}
        >
            {item.label}
        </Link>
    );
}

export default function Sidebar({ open, onClose }: SidebarProps) {
    const { url } = usePage();
    const [manual, setManual] = useState<Record<string, boolean>>({});

    const isSectionOpen = (section: NavSection): boolean => {
        if (Object.prototype.hasOwnProperty.call(manual, section.id)) {
            return manual[section.id];
        }

        return ['recruitment', 'employers', 'website'].includes(section.id) || sectionHasActive(url, section.items);
    };

    const toggle = (id: string) => {
        setManual((current) => {
            const section = sections.find((item) => item.id === id);
            const currentlyOpen = section
                ? Object.prototype.hasOwnProperty.call(current, id)
                    ? current[id]
                    : ['recruitment', 'employers', 'website'].includes(id) || sectionHasActive(url, section.items)
                : true;

            return { ...current, [id]: !currentlyOpen };
        });
    };

    return (
        <>
            {open && (
                <button
                    type="button"
                    className="fixed inset-0 z-40 bg-ink/40 lg:hidden"
                    aria-label="Close sidebar"
                    onClick={onClose}
                />
            )}

            <aside
                className={`fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-brand text-white transition-transform lg:static lg:translate-x-0 ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-16 items-center border-b border-white/10 px-5">
                    <Link href="/admin" className="min-w-0" onClick={onClose}>
                        <BrandMark inverted className="text-base" />
                        <span className="mt-0.5 block text-xs text-white/70">Admin</span>
                    </Link>
                </div>

                <nav className="flex-1 space-y-4 overflow-y-auto p-3" aria-label="Admin">
                    <NavLink item={dashboardItem} url={url} onClose={onClose} />

                    {sections.map((section) => {
                        const openSection = isSectionOpen(section);

                        return (
                            <div key={section.id}>
                                <button
                                    type="button"
                                    className="flex w-full items-center justify-between rounded-md px-3 py-1.5 text-left text-[11px] font-semibold uppercase tracking-wide text-white/55 hover:text-white"
                                    aria-expanded={openSection}
                                    onClick={() => toggle(section.id)}
                                >
                                    <span>{section.label}</span>
                                    <span aria-hidden="true">{openSection ? '−' : '+'}</span>
                                </button>
                                {openSection && (
                                    <div className="mt-1 space-y-0.5">
                                        {section.items.map((item) => (
                                            <NavLink key={item.label} item={item} url={url} onClose={onClose} />
                                        ))}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </nav>

                <div className="border-t border-white/10 p-3">
                    <Link
                        href="/admin/logout"
                        method="post"
                        as="button"
                        onClick={onClose}
                        className="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white"
                    >
                        Logout
                    </Link>
                </div>
            </aside>
        </>
    );
}
