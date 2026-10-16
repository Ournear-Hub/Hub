import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AccountNotificationsBell from '@/Components/Account/AccountNotificationsBell';
import CompanyWorkspaceMenu from '@/Components/Account/CompanyWorkspaceMenu';
import PersonalAccountMenu from '@/Components/Account/PersonalAccountMenu';
import GlobalHeaderSearch, { GlobalHeaderSearchMobile } from '@/Components/Public/GlobalHeaderSearch';
import AccountNavItemIcon from '@/Components/Account/AccountNavItemIcon';
import BrandMark from '@/Components/BrandMark';
import PublicMobileMenu from '@/Components/Public/PublicMobileMenu';
import PageContainer from '@/Components/Public/PageContainer';
import IconButton from '@/Components/UI/IconButton';
import { MenuIcon } from '@/Components/UI/icons';
import { isNavActive, primaryNav } from '@/content/navigation';
import { focusRing } from '@/design-system/classNames';
import type { AuthUser, SharedPageProps } from '@/types/page';

const MOBILE_MENU_ID = 'public-mobile-nav';

const headerMenuLinkClass = `flex items-center gap-2.5 rounded-md px-3 py-2 text-sm text-ink hover:bg-surface ${focusRing}`;

function personalDisplayName(user: AuthUser): string {
    const name = user.name?.trim();
    if (name) {
        return name;
    }

    const username = user.username?.trim();
    if (username) {
        return username;
    }

    return 'Account';
}

function ProfileAvatar({ user, className }: { user: AuthUser; className: string }) {
    return user.photo_url ? (
        <img src={user.photo_url} alt="" className={`${className} rounded-full object-cover`} />
    ) : (
        <span
            aria-hidden="true"
            className={`${className} inline-flex items-center justify-center rounded-full bg-brand text-xs font-semibold text-white`}
        >
            {(user.name.trim() || user.username || 'U').charAt(0).toUpperCase()}
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

    useEffect(() => {
        setOpen(false);
    }, [url]);

    return (
        <>
            <header className="fixed inset-x-0 top-0 z-40 border-b border-line bg-white/95 backdrop-blur-[2px]">
                <PageContainer className="grid h-16 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-2 sm:h-[4.5rem] sm:gap-3 md:grid-cols-[auto_minmax(0,1fr)_minmax(18rem,36rem)_minmax(0,1fr)_auto]">
                    <Link href="/" className="min-w-0 shrink-0" aria-label={`${props.branding?.website_name || 'OurNear'} home`}>
                        <BrandMark className="text-base sm:text-lg" usePublicBranding logosOnly placement="header" />
                    </Link>

                    <div className="hidden min-w-0 md:block" aria-hidden="true" />

                    <div className="hidden min-w-0 justify-center md:col-start-3 md:flex">
                        <GlobalHeaderSearch className="w-full min-w-[18rem] max-w-xl" />
                    </div>

                    <div className="hidden min-w-0 md:col-start-4 md:block" aria-hidden="true" />

                    <div className="col-start-3 flex shrink-0 items-center justify-end gap-2 md:col-start-5">
                        <nav className="mr-1 hidden min-w-0 items-center gap-3 lg:flex xl:gap-4" aria-label="Primary">
                            {primaryNav.map((item) => {
                                const active = isNavActive(url, item.href);
                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className={navLinkClass(active)}
                                        aria-current={active ? 'page' : undefined}
                                    >
                                        {item.label}
                                    </Link>
                                );
                            })}
                        </nav>
                        <GlobalHeaderSearchMobile />
                        {!user ? (
                            <>
                                <Link href="/login" className={`${authLinkClass} hidden text-ink hover:text-brand lg:inline-flex`}>
                                    Login
                                </Link>
                                <Link
                                    href="/register"
                                    className={`${authLinkClass} hidden border border-line text-ink hover:bg-surface lg:inline-flex`}
                                >
                                    Signup
                                </Link>
                            </>
                        ) : null}
                        {user ? (
                            <div className="flex items-center gap-2">
                                <AccountNotificationsBell />
                                {user.employer_identity ? (
                                    <CompanyWorkspaceMenu identity={user.employer_identity} className="inline-flex" />
                                ) : null}
                                <PersonalAccountMenu user={user} className="inline-flex" />
                            </div>
                        ) : null}
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

            <div className="h-16 shrink-0 sm:h-[4.5rem]" aria-hidden="true" />

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
                                className={`block min-h-11 rounded-md border border-line px-3 py-2.5 text-center text-sm font-semibold text-ink hover:bg-surface ${focusRing}`}
                                onClick={() => setOpen(false)}
                            >
                                Signup
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
                                    <p className="truncate text-sm font-semibold text-ink">{personalDisplayName(user)}</p>
                                    <p className="text-xs text-muted">Personal account</p>
                                </div>
                            </div>
                            {(props.headerAccountNav ?? []).map((item) => (
                                <Link
                                    key={`${item.href}-${item.label}`}
                                    href={item.href}
                                    className={`${headerMenuLinkClass} min-h-11 py-2.5 font-medium`}
                                    onClick={() => setOpen(false)}
                                >
                                    <AccountNavItemIcon icon={item.icon} variant="sheet" />
                                    <span className="min-w-0 truncate">{item.label}</span>
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
