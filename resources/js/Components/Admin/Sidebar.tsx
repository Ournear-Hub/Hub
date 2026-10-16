import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { adminSectionHasActive, isAdminNavActive } from '@/Components/Admin/adminNavigation';
import BrandMark from '@/Components/BrandMark';
import OurNearIcon from '@/icons/OurNearIcon';
import type { SharedPageProps } from '@/types/page';
import type { AdminNavItem, NavSection } from '@/types/layout';

const defaultOpen: string[] = [];

type SidebarProps = {
    open: boolean;
    onClose: () => void;
};

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

    const active = isAdminNavActive(url, item.href);

    return (
        <Link
            href={item.href}
            onClick={onClose}
            data-active={active ? 'true' : undefined}
            aria-current={active ? 'page' : undefined}
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
    const navRef = useRef<HTMLElement>(null);
    const [manual, setManual] = useState<Record<string, boolean>>({});
    const visibleSections: NavSection[] = props.adminNav ?? [];

    const isSectionOpen = (section: NavSection): boolean => {
        if (Object.prototype.hasOwnProperty.call(manual, section.id)) {
            return manual[section.id];
        }

        return defaultOpen.includes(section.id) || adminSectionHasActive(url, section.items);
    };

    const toggle = (id: string) => {
        setManual((current) => {
            const section = visibleSections.find((item) => item.id === id);
            const currentlyOpen = section
                ? Object.prototype.hasOwnProperty.call(current, id)
                    ? current[id]
                    : defaultOpen.includes(id) || adminSectionHasActive(url, section.items)
                : true;

            if (currentlyOpen) {
                return { ...current, [id]: false };
            }

            const next: Record<string, boolean> = {};
            visibleSections.forEach((visibleSection) => {
                next[visibleSection.id] = visibleSection.id === id;
            });

            return next;
        });
    };

    useEffect(() => {
        const nav = navRef.current;
        if (!nav) {
            return;
        }

        const stored = window.sessionStorage.getItem('ournear-admin-nav-scroll');
        if (stored) {
            nav.scrollTop = Number(stored) || 0;
        }

        const active = nav.querySelector<HTMLElement>('[data-active="true"]');
        active?.scrollIntoView({ block: 'nearest' });
    }, [url]);

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
                        <BrandMark inverted surface="admin" className="text-base" usePublicBranding />
                        <span className="mt-0.5 block text-xs text-white/70">OurNear Admin</span>
                    </Link>
                </div>

                <nav
                    ref={navRef}
                    className="min-h-0 flex-1 space-y-4 overflow-y-auto p-3"
                    aria-label="Admin"
                    onScroll={(event) => window.sessionStorage.setItem('ournear-admin-nav-scroll', String(event.currentTarget.scrollTop))}
                >
                    {visibleSections.map((section) => {
                        const openSection = isSectionOpen(section);
                        const panelId = `admin-nav-section-${section.id}`;

                        return (
                            <div key={section.id}>
                                <button
                                    type="button"
                                    className="flex w-full items-center justify-between gap-2 rounded-md px-3 py-1.5 text-left text-[11px] font-semibold uppercase tracking-wide text-white/55 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40"
                                    aria-expanded={openSection}
                                    aria-controls={panelId}
                                    onClick={() => toggle(section.id)}
                                >
                                    <span className="flex min-w-0 items-center gap-2">
                                        {section.icon ? (
                                            <OurNearIcon name={section.icon} size="sm" className="shrink-0 text-white/70" aria-hidden />
                                        ) : null}
                                        <span className="truncate">{section.label}</span>
                                    </span>
                                    <OurNearIcon
                                        name={openSection ? 'chevron-down' : 'chevron-right'}
                                        size="sm"
                                        className="shrink-0 text-white/50"
                                        aria-hidden
                                    />
                                </button>
                                {openSection && (
                                    <div id={panelId} className="mt-1 space-y-0.5" role="region" aria-label={section.label}>
                                        {section.items.map((item) => (
                                            <div key={`${section.id}-${item.label}`}>
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
