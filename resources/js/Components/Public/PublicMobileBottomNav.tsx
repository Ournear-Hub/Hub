import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import OurNearIcon from '@/icons/OurNearIcon';
import { focusRing } from '@/design-system/classNames';
import type { SharedPageProps } from '@/types/page';
import { resolvePostJobHref } from '@/utils/employerHiringEntry';

const moreLinks = [
    { label: 'Hiring', href: '/hire' },
    { label: 'Staffing', href: '/staffing' },
    { label: 'About', href: '/about' },
    { label: 'Contact', href: '/contact' },
];

function navItemClass(active: boolean) {
    return `flex min-w-0 flex-1 flex-col items-center gap-0.5 px-1 py-2 text-[10px] font-semibold leading-tight ${
        active ? 'text-brand' : 'text-muted'
    } ${focusRing}`;
}

export default function PublicMobileBottomNav() {
    const { url, props } = usePage<SharedPageProps>();
    const user = props.auth.user;
    const postJobHref = resolvePostJobHref(user);
    const path = (url.split('?')[0] ?? url).split('#')[0] ?? url;
    const [moreOpen, setMoreOpen] = useState(false);

    const isHome = path === '/';
    const isJobs = path === '/jobs' || path.startsWith('/jobs/');
    const isTalent = path === '/talent-pool' || path.startsWith('/talent-pool/');

    return (
        <>
            <nav
                className="fixed inset-x-0 bottom-0 z-50 border-t border-line bg-[#FFFFFF] pb-[env(safe-area-inset-bottom)] shadow-[0_-1px_3px_rgba(0,0,0,0.06)] lg:hidden"
                style={{ backgroundColor: '#FFFFFF' }}
                aria-label="Mobile primary"
            >
                <div className="mx-auto flex max-w-lg items-stretch justify-between">
                    <Link href="/" className={navItemClass(isHome)} aria-current={isHome ? 'page' : undefined}>
                        <OurNearIcon name="home" size="sm" className={isHome ? 'text-brand' : 'text-muted'} />
                        <span>Home</span>
                    </Link>
                    <Link href="/jobs" className={navItemClass(isJobs)} aria-current={isJobs ? 'page' : undefined}>
                        <OurNearIcon name="search" size="sm" className={isJobs ? 'text-brand' : 'text-muted'} />
                        <span>Jobs</span>
                    </Link>
                    <Link href={postJobHref} className={navItemClass(false)}>
                        <OurNearIcon name="briefcase" size="sm" className="text-accent" />
                        <span>Post Job</span>
                    </Link>
                    <Link href="/talent-pool" className={navItemClass(isTalent)} aria-current={isTalent ? 'page' : undefined}>
                        <OurNearIcon name="user" size="sm" className={isTalent ? 'text-brand' : 'text-muted'} />
                        <span>Talent</span>
                    </Link>
                    <button
                        type="button"
                        className={navItemClass(moreOpen)}
                        aria-expanded={moreOpen}
                        aria-controls="public-bottom-more-panel"
                        onClick={() => setMoreOpen((open) => !open)}
                    >
                        <OurNearIcon name="menu" size="sm" className={moreOpen ? 'text-brand' : 'text-muted'} />
                        <span>More</span>
                    </button>
                </div>
            </nav>
            {moreOpen ? (
                <div
                    id="public-bottom-more-panel"
                    className="fixed inset-x-0 bottom-[calc(3.75rem+env(safe-area-inset-bottom))] z-50 border-t border-line bg-[#FFFFFF] p-3 shadow-md lg:hidden"
                    style={{ backgroundColor: '#FFFFFF' }}
                    role="dialog"
                    aria-label="More navigation"
                >
                    <ul className="grid gap-1">
                        {moreLinks.map((item) => (
                            <li key={item.href}>
                                <Link
                                    href={item.href}
                                    className={`block rounded-md px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface ${focusRing}`}
                                    onClick={() => setMoreOpen(false)}
                                >
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                        {!user ? (
                            <>
                                <li>
                                    <Link
                                        href="/login"
                                        className={`block rounded-md px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface ${focusRing}`}
                                        onClick={() => setMoreOpen(false)}
                                    >
                                        Login
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href="/register"
                                        className={`block rounded-md px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface ${focusRing}`}
                                        onClick={() => setMoreOpen(false)}
                                    >
                                        Register
                                    </Link>
                                </li>
                            </>
                        ) : null}
                    </ul>
                </div>
            ) : null}
        </>
    );
}
