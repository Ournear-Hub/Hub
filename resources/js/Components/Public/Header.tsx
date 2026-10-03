import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import BrandMark from '@/Components/BrandMark';
import PublicMobileMenu from '@/Components/Public/PublicMobileMenu';
import PageContainer from '@/Components/Public/PageContainer';
import IconButton from '@/Components/UI/IconButton';
import { MenuIcon } from '@/Components/UI/icons';
import { isNavActive, primaryNav } from '@/content/navigation';
import { focusRing } from '@/design-system/classNames';
import type { AuthUser, SharedPageProps } from '@/types/page';

type AccountLink = {
    href: string;
    label: string;
};

const MOBILE_MENU_ID = 'public-mobile-nav';

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

const navLinkClass = (active: boolean) =>
    `border-b-2 pb-0.5 text-sm font-medium transition-colors ${active ? 'border-brand text-brand' : 'border-transparent text-muted hover:border-line hover:text-ink'}`;

const authLinkClass = `inline-flex min-h-10 items-center whitespace-nowrap rounded-md px-3 text-sm font-semibold ${focusRing}`;

export default function Header() {
    const { url, props } = usePage<SharedPageProps>();
    const user = props.auth.user;
    const [open, setOpen] = useState(false);
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
        <>
            <header className="sticky top-0 z-40 border-b border-line bg-white">
                <PageContainer className="flex h-14 items-center justify-between gap-3 sm:h-16 sm:gap-4">
                    <Link href="/" className="min-w-0 shrink-0" aria-label={`${props.branding?.website_name || 'OurNear'} home`}>
                        <BrandMark className="text-base sm:text-lg" usePublicBranding />
                    </Link>

                    <nav className="hidden items-center gap-4 xl:gap-5 lg:flex" aria-label="Primary">
                        {primaryNav.map((item) => {
                            const active = isNavActive(url, item.href);
                            return (
                                <Link key={item.href} href={item.href} className={navLinkClass(active)} aria-current={active ? 'page' : undefined}>
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="flex shrink-0 items-center gap-2">
                        {!user ? (
                            <>
                                <Link href="/login" className={`${authLinkClass} hidden text-ink hover:text-brand lg:inline-flex`}>
                                    Login
                                </Link>
                                <Link
                                    href="/register"
                                    className={`${authLinkClass} hidden bg-brand text-white hover:bg-brand/90 lg:inline-flex`}
                                >
                                    Register
                                </Link>
                            </>
                        ) : (
                            <div className="relative hidden lg:block" ref={profileRef}>
                                <button
                                    type="button"
                                    className={`flex min-h-10 items-center gap-2 rounded-full border border-line bg-white py-1 pl-1 pr-3 text-left hover:bg-surface ${focusRing}`}
                                    aria-haspopup="menu"
                                    aria-expanded={profileOpen}
                                    aria-label={`Open account menu for ${shortName(user)}`}
                                    onClick={() => setProfileOpen((value) => !value)}
                                >
                                    <ProfileAvatar user={user} className="h-8 w-8 shrink-0" />
                                    <span className="max-w-28 truncate text-sm font-medium text-ink">{shortName(user)}</span>
                                    <span aria-hidden="true" className="text-xs text-muted">
                                        ▾
                                    </span>
                                </button>
                                {profileOpen ? (
                                    <div
                                        role="menu"
                                        aria-label="Account menu"
                                        className="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-line bg-white shadow-md"
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
                                                    className={`block rounded-md px-3 py-2 text-sm text-ink hover:bg-surface ${focusRing}`}
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
                                                className={`block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-danger hover:bg-danger/5 ${focusRing}`}
                                                onClick={() => setProfileOpen(false)}
                                            >
                                                Log out
                                            </Link>
                                        </div>
                                    </div>
                                ) : null}
                            </div>
                        )}
                        <IconButton
                            label={open ? 'Close navigation menu' : 'Open navigation menu'}
                            className="lg:hidden"
                            aria-expanded={open}
                            aria-controls={MOBILE_MENU_ID}
                            onClick={() => setOpen((value) => !value)}
                        >
                            <MenuIcon />
                        </IconButton>
                    </div>
                </PageContainer>
            </header>

            <PublicMobileMenu
                open={open}
                onClose={() => setOpen(false)}
                url={url}
                panelId={MOBILE_MENU_ID}
                primaryItems={primaryNav}
                guestActions={
                    !user ? (
                        <>
                            <Link
                                href="/login"
                                className={`block min-h-11 rounded-md px-3 py-2.5 text-sm font-semibold text-ink hover:bg-surface ${focusRing}`}
                                onClick={() => setOpen(false)}
                            >
                                Login
                            </Link>
                            <Link
                                href="/register"
                                className={`block min-h-11 rounded-md bg-brand px-3 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand/90 ${focusRing}`}
                                onClick={() => setOpen(false)}
                            >
                                Register
                            </Link>
                        </>
                    ) : undefined
                }
                accountSection={
                    user ? (
                        <div>
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
                                    className={`block min-h-11 rounded-md px-3 py-2.5 text-sm font-medium text-ink hover:bg-surface ${focusRing}`}
                                    onClick={() => setOpen(false)}
                                >
                                    {item.label}
                                </Link>
                            ))}
                            <button
                                type="button"
                                className={`mt-2 min-h-11 w-full rounded-md px-3 py-2.5 text-left text-sm font-medium text-danger hover:bg-danger/5 ${focusRing}`}
                                onClick={() => {
                                    setOpen(false);
                                    router.post('/logout');
                                }}
                            >
                                Log out
                            </button>
                        </div>
                    ) : undefined
                }
            />
        </>
    );
}
