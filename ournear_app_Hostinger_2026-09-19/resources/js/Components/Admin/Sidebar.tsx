import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BrandMark from '@/Components/BrandMark';
import type { SharedPageProps } from '@/types/page';
import type { AdminNavItem, NavSection } from '@/types/layout';

const dashboardItem: AdminNavItem = { label: 'Dashboard', href: '/admin' };

const sections: NavSection[] = [
    {
        id: 'platform',
        label: 'Platform',
        items: [
            {
                label: 'Users',
                href: '/admin/users',
                permission: 'users.view',
                children: [
                    { label: 'All Users', href: '/admin/users', permission: 'users.view' },
                    { label: 'Active Users', href: '/admin/users?segment=active', permission: 'users.view' },
                    { label: 'Inactive Users', href: '/admin/users?segment=inactive', permission: 'users.view' },
                    { label: 'Blocked Users', href: '/admin/users?segment=blocked', permission: 'users.view' },
                    { label: 'Suspended Users', href: '/admin/users?segment=suspended', permission: 'users.view' },
                    { label: 'Verified Users', href: '/admin/users?segment=verified', permission: 'users.view' },
                    { label: 'Unverified Users', href: '/admin/users?segment=unverified', permission: 'users.view' },
                    { label: 'New Users', href: '/admin/users?segment=new', permission: 'users.view' },
                    { label: 'Candidates', href: '/admin/users?segment=candidates', permission: 'users.view' },
                    { label: 'Employers', href: '/admin/users?segment=employers', permission: 'users.view' },
                    { label: 'User Activity', href: '/admin/users/activity', permission: 'users.view' },
                ],
            },
            { label: 'Admin Users', href: '/admin/staff', permission: 'users.view' },
            { label: 'Roles & Permissions', href: '/admin/roles', permission: 'roles.manage' },
            { label: 'Activity Logs', href: '/admin/activity-logs', permission: 'security.view' },
            { label: 'Login Logs', href: '/admin/login-logs', permission: 'security.view' },
        ],
    },
    {
        id: 'recruitment',
        label: 'Recruitment',
        items: [
            { label: 'Jobs', href: '/admin/jobs', permission: 'jobs.view' },
            { label: 'Candidates', href: '/admin/candidates', permission: 'candidates.view' },
            { label: 'Freelancers', href: '/admin/freelancers', permission: 'freelancers.view' },
            { label: 'Applications', href: '/admin/applications', permission: 'applications.view' },
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
            { label: 'Employer Enquiries', href: '/admin/employer-enquiries', permission: 'employers.view' },
        ],
    },
    {
        id: 'finance',
        label: 'Finance',
        items: [
            { label: 'Invoices', href: '/admin/finance?view=invoices', permission: 'finance.view' },
            { label: 'Payments', href: '/admin/finance?view=payments', permission: 'finance.view' },
        ],
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
        id: 'website',
        label: 'Website',
        items: [
            { label: 'Content', href: '/admin/website-content', permission: 'website.manage' },
            { label: 'Media & Social', href: '/admin/media-social', permission: 'website.manage' },
            { label: 'Settings', href: '/admin/settings', permission: 'website.manage' },
        ],
    },
    {
        id: 'master-data',
        label: 'Master Data',
        items: [
            { label: 'Industries', href: '/admin/master-data?tab=industries', permission: 'master_data.manage' },
            { label: 'Categories', href: '/admin/master-data?tab=categories', permission: 'master_data.manage' },
            { label: 'Job Titles', href: '/admin/master-data?tab=job-titles', permission: 'master_data.manage' },
            { label: 'Locations', href: '/admin/master-data?tab=locations', permission: 'master_data.manage' },
            { label: 'Other Master Data', href: '/admin/master-data', permission: 'master_data.manage' },
        ],
    },
];

const defaultOpen = ['platform', 'recruitment', 'employers', 'website'];

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
    return items
        .map((item) => ({
            ...item,
            children: item.children ? visibleItems(item.children, permissions) : undefined,
        }))
        .filter((item) => item.comingSoon || hasPermission(permissions, item.permission) || (item.children && item.children.length > 0));
}

function parseUrl(url: string): { path: string; params: URLSearchParams } {
    const [path, query = ''] = url.split('?');

    return { path, params: new URLSearchParams(query) };
}

function isActive(url: string, href: string): boolean {
    const current = parseUrl(url);
    const target = parseUrl(href);

    if (target.path === '/admin') {
        return current.path === '/admin' || current.path === '/admin/';
    }

    if (target.path === '/admin/users' && !target.params.get('segment')) {
        if (current.path === '/admin/users/activity' || current.path.startsWith('/admin/users/')) {
            return false;
        }

        return current.path === '/admin/users' && !current.params.get('segment');
    }

    if (target.params.get('segment')) {
        return current.path === '/admin/users' && current.params.get('segment') === target.params.get('segment');
    }

    if (target.params.get('view')) {
        const currentView =
            current.params.get('view') ||
            (current.path === '/admin/finance' && current.params.get('payment') ? 'payments' : current.path === '/admin/finance' ? 'invoices' : null);

        return current.path === target.path && currentView === target.params.get('view');
    }

    if (target.params.get('tab')) {
        return current.path === target.path && current.params.get('tab') === target.params.get('tab');
    }

    if (target.path === '/admin/master-data' && !target.params.get('tab')) {
        return current.path === '/admin/master-data' && !current.params.get('tab');
    }

    return current.path === target.path || current.path.startsWith(`${target.path}/`);
}

function sectionHasActive(url: string, items: AdminNavItem[]): boolean {
    return items.some((item) => {
        if (item.href && isActive(url, item.href)) {
            return true;
        }

        if (item.children && sectionHasActive(url, item.children)) {
            return true;
        }

        if (item.href === '/admin/users' && (url.startsWith('/admin/users/') || url.startsWith('/admin/users?'))) {
            return true;
        }

        return false;
    });
}

function NavLink({ item, url, onClose, nested = false }: { item: AdminNavItem; url: string; onClose: () => void; nested?: boolean }) {
    if (item.comingSoon || !item.href) {
        return (
            <span
                className={`flex items-center justify-between rounded-md px-3 py-2 text-sm text-white/45 ${nested ? 'pl-6' : ''}`}
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
            className={`block rounded-md px-3 py-2 text-sm font-medium ${nested ? 'pl-6 text-[13px]' : ''} ${
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

        return defaultOpen.includes(section.id) || sectionHasActive(url, section.items);
    };

    const toggle = (id: string) => {
        setManual((current) => {
            const section = visibleSections.find((item) => item.id === id);
            const currentlyOpen = section
                ? Object.prototype.hasOwnProperty.call(current, id)
                    ? current[id]
                    : defaultOpen.includes(id) || sectionHasActive(url, section.items)
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
                className={`fixed inset-y-0 left-0 z-50 flex h-screen w-64 shrink-0 flex-col bg-brand text-white transition-transform lg:static lg:translate-x-0 ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-16 shrink-0 items-center border-b border-white/10 px-5">
                    <Link href="/admin" className="min-w-0" onClick={onClose}>
                        <BrandMark inverted className="text-base" />
                        <span className="mt-0.5 block text-xs text-white/70">Admin</span>
                    </Link>
                </div>

                <nav className="min-h-0 flex-1 space-y-4 overflow-y-auto p-3" aria-label="Admin">
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
                                            <div key={item.label}>
                                                <NavLink item={item} url={url} onClose={onClose} />
                                                {item.children?.map((child) => (
                                                    <NavLink key={child.label} item={child} url={url} onClose={onClose} nested />
                                                ))}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </nav>

                <div className="shrink-0 border-t border-white/10 p-3">
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
