import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import BrandMark from '@/Components/BrandMark';
import PageContainer from '@/Components/Public/PageContainer';
import { isNavActive, primaryNav } from '@/content/navigation';
import type { AuthUser, SharedPageProps } from '@/types/page';

type AccountLink = {
    href: string;
    label: string;
};

function shortName(user: AuthUser): string {
    return user.name.trim().split(/\s+/)[0] || user.username || 'Account';
}

function initials(user: AuthUser): string {
    const source = user.name.trim() || user.username || 'User';

    return source
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();
}

function ProfileAvatar({ user, className }: { user: AuthUser; className: string }) {
    return user.photo_url ? (
        <img src={user.photo_url} alt={`${user.name} profile`} className={`${className} rounded-full object-cover`} />
    ) : (
        <span
            aria-label={`${user.name} initials`}
            className={`${className} inline-flex items-center justify-center rounded-full bg-brand text-xs font-semibold text-white`}
        >
            {initials(user)}
        </span>
    );
}

export default function Header() {
    const { url, props } = usePage<SharedPageProps>();
    const user = props.auth.user;
    const [open, setOpen] = useState(false);
    const [servicesOpen, setServicesOpen] = useState(false);
    const [profileOpen, setProfileOpen] = useState(false);
    const profileRef = useRef<HTMLDivElement>(null);
    const accountLinks: AccountLink[] = user?.role === 'candidate'
        ? props.candidateNav ?? []
        : user?.role === 'employer'
          ? [
                { href: '/account', label: 'Dashboard' },
                { href: '/account/profile', label: 'Company Profile' },
                { href: '/account/jobs', label: 'Job Management' },
                { href: '/account/enquiries', label: 'Enquiries' },
                { href: '/account/settings', label: 'Settings' },
            ]
          : user?.role === 'admin'
            ? [
                  { href: '/admin', label: 'Admin Panel' },
                  { href: '/admin/profile', label: 'Profile' },
              ]
            : [];
    const visiblePrimaryNav = user?.role === 'employer'
        ? primaryNav.filter((item) => item.href !== '/for-employers')
        : primaryNav;

    useEffect(() => {
        if (!profileOpen) {
            return;
        }

        const closeOutside = (event: MouseEvent) => {
            if (profileRef.current && !profileRef.current.contains(event.target as Node)) {
                setProfileOpen(false);
            }
        };
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setProfileOpen(false);
            }
        };

        window.addEventListener('mousedown', closeOutside);
        window.addEventListener('keydown', closeOnEscape);

        return () => {
            window.removeEventListener('mousedown', closeOutside);
            window.removeEventListener('keydown', closeOnEscape);
        };
    }, [profileOpen]);

    useEffect(() => {
        setOpen(false);
        setProfileOpen(false);
    }, [url]);

    return (
        <header className="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
            <PageContainer className="flex h-16 items-center justify-between gap-4">
                <Link href="/" className="shrink-0" aria-label={`${props.branding?.website_name || 'OurNear Talent'} home`}>
                    <BrandMark className="text-lg" usePublicBranding />
                </Link>

                <nav className="hidden items-center gap-5 lg:flex" aria-label="Primary">
                    {visiblePrimaryNav.map((item) => {
                        const active = isNavActive(url, item.href);

                        if (item.children) {
                            return (
                                <div key={item.href} className="relative">
                                    <button
                                        type="button"
                                        className={`text-sm font-medium ${active ? 'text-brand' : 'text-muted hover:text-ink'}`}
                                        aria-expanded={servicesOpen}
                                        aria-haspopup="true"
                                        onClick={() => setServicesOpen((value) => !value)}
                                        onBlur={() => window.setTimeout(() => setServicesOpen(false), 150)}
                                    >
                                        {item.label}
                                    </button>
                                    {servicesOpen && (
                                        <div className="absolute left-0 mt-2 w-52 rounded-md border border-line bg-white py-2 shadow-sm">
                                            <Link
                                                href={item.href}
                                                className="block px-3 py-2 text-sm text-ink hover:bg-surface"
                                            >
                                                All services
                                            </Link>
                                            {item.children.map((child) => (
                                                <Link
                                                    key={child.href}
                                                    href={child.href}
                                                    className="block px-3 py-2 text-sm text-muted hover:bg-surface hover:text-ink"
                                                >
                                                    {child.label}
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            );
                        }

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`text-sm font-medium ${active ? 'text-brand' : 'text-muted hover:text-ink'}`}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="flex shrink-0 items-center gap-2 lg:gap-3">
                    {!user ? (
                        <>
                            <Link
                                href="/login"
                                className="hidden min-h-10 items-center whitespace-nowrap rounded-md border border-line px-3 text-sm font-semibold text-ink hover:border-brand hover:text-brand sm:inline-flex"
                            >
                                Log in
                            </Link>
                            <Link
                                href="/register"
                                className="hidden min-h-10 items-center whitespace-nowrap rounded-md bg-brand px-3 text-sm font-semibold text-white shadow-sm hover:bg-brand/90 sm:inline-flex"
                            >
                                Register
                            </Link>
                        </>
                    ) : (
                        <div className="relative hidden lg:block" ref={profileRef}>
                            <button
                                type="button"
                                className="flex min-h-11 items-center gap-2 rounded-full border border-line bg-white py-1 pl-1 pr-3 text-left shadow-sm hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40"
                                aria-haspopup="menu"
                                aria-expanded={profileOpen}
                                aria-label={`Open account menu for ${shortName(user)}`}
                                onClick={() => setProfileOpen((value) => !value)}
                            >
                                <ProfileAvatar user={user} className="h-9 w-9 shrink-0" />
                                <span className="max-w-28 truncate text-sm font-medium text-ink">{shortName(user)}</span>
                                <span aria-hidden="true" className="text-xs text-muted">
                                    ▾
                                </span>
                            </button>
                            {profileOpen ? (
                                <div
                                    role="menu"
                                    aria-label="Account menu"
                                    className="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-line bg-white shadow-lg"
                                >
                                    <div className="flex items-center gap-3 border-b border-line px-4 py-3">
                                        <ProfileAvatar user={user} className="h-11 w-11 shrink-0" />
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold text-ink">{user.name}</p>
                                            <p className="text-xs text-muted">Your account</p>
                                        </div>
                                    </div>
                                    <nav className="max-h-[min(26rem,calc(100vh-8rem))] overflow-y-auto p-2" aria-label="Account menu links">
                                        {accountLinks.map((item) => (
                                            <Link
                                                key={item.href}
                                                href={item.href}
                                                role="menuitem"
                                                className="block rounded-md px-3 py-2 text-sm text-ink hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/30"
                                                onClick={() => setProfileOpen(false)}
                                            >
                                                {item.label}
                                            </Link>
                                        ))}
                                    </nav>
                                    <div className="border-t border-line p-2">
                                        <Link
                                            href="/logout"
                                            method="post"
                                            as="button"
                                            role="menuitem"
                                            className="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-danger hover:bg-danger/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger/30"
                                            onClick={() => setProfileOpen(false)}
                                        >
                                            Log out
                                        </Link>
                                    </div>
                                </div>
                            ) : null}
                        </div>
                    )}
                    <button
                        type="button"
                        className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink lg:hidden"
                        aria-expanded={open}
                        aria-controls="public-mobile-nav"
                        onClick={() => setOpen((value) => !value)}
                    >
                        <span className="sr-only">Toggle navigation</span>
                        <span className="flex flex-col gap-1.5">
                            <span className="block h-0.5 w-4 bg-current" />
                            <span className="block h-0.5 w-4 bg-current" />
                            <span className="block h-0.5 w-4 bg-current" />
                        </span>
                    </button>
                </div>
            </PageContainer>

            {open && (
                <div id="public-mobile-nav" className="border-t border-line bg-white lg:hidden">
                    <PageContainer className="flex flex-col gap-1 py-3">
                        {visiblePrimaryNav.map((item) => (
                            <div key={item.href}>
                                <Link
                                    href={item.href}
                                    className="block rounded-md px-2 py-2 text-sm font-medium text-ink hover:bg-surface"
                                    onClick={() => setOpen(false)}
                                >
                                    {item.label}
                                </Link>
                                {item.children?.map((child) => (
                                    <Link
                                        key={child.href}
                                        href={child.href}
                                        className="block rounded-md px-4 py-2 text-sm text-muted hover:bg-surface"
                                        onClick={() => setOpen(false)}
                                    >
                                        {child.label}
                                    </Link>
                                ))}
                            </div>
                        ))}
                        {!user ? (
                            <>
                                <Link
                                    href="/login"
                                    className="rounded-md px-2 py-2 text-sm font-medium text-ink hover:bg-surface"
                                    onClick={() => setOpen(false)}
                                >
                                    Log in
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-md px-2 py-2 text-sm font-medium text-ink hover:bg-surface"
                                    onClick={() => setOpen(false)}
                                >
                                    Register
                                </Link>
                            </>
                        ) : (
                            <div className="mt-2 border-t border-line pt-3">
                                <div className="mb-2 flex items-center gap-3 rounded-lg bg-surface px-3 py-3">
                                    <ProfileAvatar user={user} className="h-11 w-11 shrink-0" />
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-ink">{shortName(user)}</p>
                                        <p className="text-xs text-muted">Your account</p>
                                    </div>
                                </div>
                                {accountLinks.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className="block min-h-11 rounded-md px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/30"
                                        onClick={() => setOpen(false)}
                                    >
                                        {item.label}
                                    </Link>
                                ))}
                                <button
                                    type="button"
                                    className="mt-1 min-h-11 w-full border-t border-line px-3 py-2.5 text-left text-sm font-medium text-danger hover:bg-danger/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger/30"
                                    onClick={() => {
                                        setOpen(false);
                                        router.post('/logout');
                                    }}
                                >
                                    Log out
                                </button>
                            </div>
                        )}
                    </PageContainer>
                </div>
            )}
        </header>
    );
}
