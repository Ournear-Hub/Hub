import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BrandMark from '@/Components/BrandMark';
import type { SharedPageProps } from '@/types/page';
import type { AdminNavItem, NavSection } from '@/types/layout';

const dashboardItem: AdminNavItem = { label: 'Dashboard', href: '/admin', core: true };

const sections: NavSection[] = [
    {
        id: 'recruitment',
        label: 'Recruitment',
        items: [
            { label: 'Jobs', href: '/admin/jobs', permission: 'jobs.view', core: true },
            { label: 'Candidates', href: '/admin/candidates', permission: 'candidates.view' },
            { label: 'Applications', href: '/admin/applications', permission: 'applications.view', core: true },
            { label: 'Screening', href: '/admin/screening', permission: 'applications.view' },
            { label: 'Shortlisted', href: '/admin/shortlisted', permission: 'applications.view' },
            { label: 'Employer Submissions', href: '/admin/submissions', permission: 'submissions.view' },
            { label: 'Placements', href: '/admin/placements', permission: 'placements.view' },
            { label: 'Joined / Employees', href: '/admin/employees', permission: 'employees.view' },
            { label: 'Replacements', href: '/admin/replacements', permission: 'replacements.view' },
        ],
    },
    {
        id: 'employers',
        label: 'Employers',
        items: [
            { label: 'Employers', href: '/admin/employers', permission: 'employers.view' },
            { label: 'Employer Enquiries', href: '/admin/employer-enquiries', permission: 'employers.view', core: true },
        ],
    },
    {
        id: 'finance',
        label: 'Finance',
        items: [{ label: 'Payments', href: '/admin/finance', permission: 'finance.view' }],
    },
    {
        id: 'communication',
        label: 'Communication',
        items: [
            { label: 'Notifications', href: '/admin/notifications', permission: 'notifications.view' },
            { label: 'Messages', href: '/admin/messages', permission: 'messages.manage' },
        ],
    },
    {
        id: 'users',
        label: 'Users & Security',
        items: [
            { label: 'Users', href: '/admin/users', permission: 'users.view' },
            { label: 'Activity Logs', href: '/admin/activity-logs', permission: 'security.view' },
            { label: 'Login Logs', href: '/admin/login-logs', permission: 'security.view' },
            { label: 'Approval Requests', comingSoon: true },
        ],
    },
    {
        id: 'website',
        label: 'Website',
        items: [
            { label: 'Website Content', href: '/admin/website-content', permission: 'website.manage', core: true },
            { label: 'Media & Social', href: '/admin/media-social', permission: 'website.manage', core: true },
            { label: 'Settings', href: '/admin/settings', core: true },
        ],
    },
    {
        id: 'administration',
        label: 'Administration',
        items: [
            { label: 'Admin Users', comingSoon: true },
            { label: 'Roles & Permissions', href: '/admin/roles', permission: 'roles.manage' },
            { label: 'Master Data', href: '/admin/master-data', permission: 'master_data.manage' },
        ],
    },
];

type SidebarProps = {
    open: boolean;
    onClose: () => void;
};

function hasPermission(permissions: string[] | undefined, required?: string): boolean {
    if (!required) {
        return true;
    }

    return (permissions ?? []).includes(required) || (permissions ?? []).includes(required.replace('.view', '.manage'));
}

function visibleItems(items: AdminNavItem[], permissions: string[] | undefined): AdminNavItem[] {
    return items.filter((item) => {
        if (item.comingSoon || item.core || !item.permission) {
            return true;
        }

        return hasPermission(permissions, item.permission);
    });
}

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
    const { url, props } = usePage<SharedPageProps>();
    const permissions = props.auth.user?.permissions;
    const [manual, setManual] = useState<Record<string, boolean>>({});
    const visibleSections = sections
        .map((section) => ({ ...section, items: visibleItems(section.items, permissions) }))
        .filter((section) => section.items.length > 0);

    const isSectionOpen = (section: NavSection): boolean => {
        if (Object.prototype.hasOwnProperty.call(manual, section.id)) {
            return manual[section.id];
        }

        return ['recruitment', 'employers', 'website'].includes(section.id) || sectionHasActive(url, section.items);
    };

    const toggle = (id: string) => {
        setManual((current) => {
            const section = visibleSections.find((item) => item.id === id);
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

                    {visibleSections.map((section) => {
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
