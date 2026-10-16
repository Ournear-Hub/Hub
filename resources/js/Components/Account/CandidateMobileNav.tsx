import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AccountNavItemIcon from '@/Components/Account/AccountNavItemIcon';
import IconButton from '@/Components/UI/IconButton';
import {
    BriefcaseIcon,
    ClipboardIcon,
    CloseIcon,
    HomeIcon,
    MoreHorizontalIcon,
    UserCircleIcon,
} from '@/Components/UI/icons';
import { focusRing } from '@/design-system/classNames';
import { matchAccountNavHref } from '@/lib/accountNavMatch';
import type { SharedPageProps } from '@/types/page';

const MOBILE_NAV_ID = 'candidate-mobile-more';

type Tab = {
    href: string;
    label: string;
    match: (path: string) => boolean;
    icon: typeof HomeIcon;
};

const tabs: Tab[] = [
    {
        href: '/account',
        label: 'Home',
        icon: HomeIcon,
        match: (path) => path === '/account' || path === '/account/',
    },
    {
        href: '/jobs',
        label: 'Jobs',
        icon: BriefcaseIcon,
        match: (path) => path === '/jobs' || path.startsWith('/jobs/'),
    },
    {
        href: '/account/applications',
        label: 'Applications',
        icon: ClipboardIcon,
        match: (path) => path.startsWith('/account/applications'),
    },
    {
        href: '/account/profile',
        label: 'Profile',
        icon: UserCircleIcon,
        match: (path) =>
            path === '/account/profile' ||
            path.startsWith('/account/profile/') ||
            path.startsWith('/account/profile#'),
    },
];

function tabClass(active: boolean): string {
    return `flex min-h-[3rem] min-w-0 flex-1 flex-col items-center justify-center gap-0.5 px-1 text-[10px] font-medium leading-tight sm:text-xs ${active ? 'text-brand' : 'text-muted'} ${focusRing}`;
}

export default function CandidateMobileNav() {
    const { url, props } = usePage<SharedPageProps>();
    const path = url.split('#')[0]?.split('?')[0] ?? url;
    const [locationHash, setLocationHash] = useState('');
    const [moreOpen, setMoreOpen] = useState(false);
    const candidateItems = props.candidateNav ?? [];
    const moreActive = !tabs.some((tab) => tab.match(path));

    useEffect(() => {
        setMoreOpen(false);
    }, [url]);

    useEffect(() => {
        const syncHash = () => setLocationHash(window.location.hash);
        syncHash();
        window.addEventListener('hashchange', syncHash);
        window.addEventListener('popstate', syncHash);

        return () => {
            window.removeEventListener('hashchange', syncHash);
            window.removeEventListener('popstate', syncHash);
        };
    }, [url]);

    useEffect(() => {
        if (!moreOpen) {
            return;
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setMoreOpen(false);
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [moreOpen]);

    return (
        <>
            <nav
                className="resume-print-hide fixed inset-x-0 bottom-0 z-40 border-t border-line bg-white pb-[env(safe-area-inset-bottom,0px)] lg:hidden"
                aria-label="Candidate mobile"
            >
                <ul className="mx-auto flex max-w-lg items-stretch justify-between px-1 pt-1">
                    {tabs.map((tab) => {
                        const active = tab.match(path);
                        const Icon = tab.icon;
                        return (
                            <li key={tab.href} className="flex min-w-0 flex-1">
                                <Link href={tab.href} className={tabClass(active)} aria-current={active ? 'page' : undefined}>
                                    <Icon className={`h-5 w-5 shrink-0 ${active ? 'text-brand' : ''}`} />
                                    <span className="truncate">{tab.label}</span>
                                </Link>
                            </li>
                        );
                    })}
                    <li className="flex min-w-0 flex-1">
                        <button
                            type="button"
                            className={tabClass(moreActive)}
                            aria-expanded={moreOpen}
                            aria-controls={MOBILE_NAV_ID}
                            onClick={() => setMoreOpen((value) => !value)}
                        >
                            <MoreHorizontalIcon className="h-5 w-5 shrink-0" />
                            <span>More</span>
                        </button>
                    </li>
                </ul>
            </nav>

            {moreOpen ? (
                <div className="fixed inset-0 z-50 lg:hidden" role="presentation">
                    <button type="button" className="absolute inset-0 bg-ink/40" aria-label="Close menu" onClick={() => setMoreOpen(false)} />
                    <div
                        id={MOBILE_NAV_ID}
                        role="dialog"
                        aria-modal="true"
                        aria-label="More account navigation"
                        className="absolute inset-x-0 bottom-0 max-h-[min(85vh,32rem)] overflow-hidden rounded-t-2xl border border-line bg-white shadow-lg"
                    >
                        <div className="flex items-center justify-between border-b border-line px-4 py-3">
                            <p className="text-sm font-semibold text-ink">More</p>
                            <IconButton label="Close menu" onClick={() => setMoreOpen(false)}>
                                <CloseIcon />
                            </IconButton>
                        </div>
                        <nav className="max-h-[calc(min(85vh,32rem)-3.5rem)] overflow-y-auto p-3" aria-label="Account">
                            {candidateItems.map((item, index) => {
                                const group = item.group;
                                const previous = candidateItems[index - 1]?.group;
                                const active = matchAccountNavHref(item.href, path, locationHash);

                                return (
                                    <div key={`${item.href}-${item.label}`}>
                                        {group && group !== previous ? (
                                            <p className="px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wide text-muted">{group}</p>
                                        ) : null}
                                        <Link
                                            href={item.href}
                                            className={`flex min-h-11 items-center gap-2.5 rounded-md px-3 py-2.5 text-sm font-medium ${active ? 'bg-brand/10 text-brand' : 'text-ink hover:bg-surface'}`}
                                            onClick={() => setMoreOpen(false)}
                                        >
                                            <AccountNavItemIcon icon={item.icon} active={active} variant="sheet" />
                                            <span className="min-w-0 truncate">{item.label}</span>
                                        </Link>
                                    </div>
                                );
                            })}
                            <button
                                type="button"
                                className="mt-2 min-h-11 w-full rounded-md px-3 py-2.5 text-left text-sm font-medium text-danger hover:bg-danger/5"
                                onClick={() => {
                                    setMoreOpen(false);
                                    router.post('/logout');
                                }}
                            >
                                Log out
                            </button>
                        </nav>
                    </div>
                </div>
            ) : null}
        </>
    );
}

/** Bottom padding so fixed nav does not cover content (candidate mobile). */
export const candidateMobileNavSpacerClass = 'pb-[calc(4.25rem+env(safe-area-inset-bottom,0px))] lg:pb-0';
