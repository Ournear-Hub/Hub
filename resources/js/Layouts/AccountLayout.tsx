import { useState, type ReactNode } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AccountNav from '@/Components/Account/AccountNav';
import CandidateMobileNav, { candidateMobileNavSpacerClass } from '@/Components/Account/CandidateMobileNav';
import IconButton from '@/Components/UI/IconButton';
import { BellIcon } from '@/Components/UI/icons';
import Breadcrumbs, { type BreadcrumbItem } from '@/Components/Navigation/Breadcrumbs';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import type { SharedPageProps } from '@/types/page';

type AccountLayoutProps = {
    title: string;
    description?: string;
    action?: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function AccountLayout({ title, description, action, breadcrumbs, children }: AccountLayoutProps) {
    const page = usePage<SharedPageProps>().props;
    const user = page.auth.user;
    const unread = page.accountAlerts?.unread ?? 0;
    const [open, setOpen] = useState(false);
    const isCandidate = user?.role === 'candidate';
    const showNotificationsBell = page.accountAlerts != null;

    return (
        <PublicLayout title={title}>
            <Head title={title} />
            <section className="resume-print-hide border-b border-line bg-white">
                <PageContainer className="flex flex-wrap items-center justify-between gap-3 py-4 sm:py-5">
                    <div className="min-w-0 flex-1">
                        {breadcrumbs ? (
                            <div className="hidden sm:block">
                                <Breadcrumbs items={breadcrumbs} />
                            </div>
                        ) : null}
                        <h1 className="truncate text-xl font-semibold text-ink sm:text-2xl">{title}</h1>
                        {description ? <p className="mt-1 hidden text-sm text-muted sm:block">{description}</p> : null}
                    </div>
                    <div className="flex shrink-0 items-center gap-2 sm:gap-3">
                        {action}
                        {showNotificationsBell ? (
                            <span className="relative">
                                <IconButton href="/account/notifications" label="Notifications">
                                    <BellIcon />
                                </IconButton>
                                {unread > 0 ? (
                                    <span className="pointer-events-none absolute right-1 top-1 h-2 w-2 rounded-full bg-accent" />
                                ) : null}
                            </span>
                        ) : null}
                        {user ? (
                            <Link
                                href="/account/profile"
                                className="flex min-w-0 max-w-[10rem] items-center gap-2 sm:max-w-[12rem]"
                            >
                                {user.photo_url ? (
                                    <img
                                        src={user.photo_url}
                                        alt=""
                                        className={`h-9 w-9 shrink-0 object-cover ${user.role === 'employer' ? 'rounded-lg' : 'rounded-full'}`}
                                    />
                                ) : (
                                    <div
                                        className={`flex h-9 w-9 shrink-0 items-center justify-center bg-surface text-xs font-semibold text-brand ${user.role === 'employer' ? 'rounded-lg' : 'rounded-full'}`}
                                    >
                                        {initials(user.display_name ?? user.name)}
                                    </div>
                                )}
                                <span className="truncate text-sm font-medium text-ink hover:text-brand">
                                    {user.display_name ?? user.name}
                                </span>
                            </Link>
                        ) : null}
                        {!isCandidate ? (
                            <button
                                type="button"
                                className="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink lg:hidden"
                                aria-expanded={open}
                                onClick={() => setOpen((value) => !value)}
                            >
                                Menu
                            </button>
                        ) : null}
                    </div>
                </PageContainer>
            </section>
            <section className={`py-6 sm:py-8 ${isCandidate ? candidateMobileNavSpacerClass : ''}`}>
                <PageContainer className="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-8">
                    <aside className={`resume-print-hide ${isCandidate ? 'hidden lg:block' : open ? 'block' : 'hidden lg:block'}`}>
                        <div className="mb-4 rounded-xl border border-line bg-white p-4">
                            {user?.photo_url ? (
                                <img
                                    src={user.photo_url}
                                    alt=""
                                    className={`h-16 w-16 object-cover ${user?.role === 'employer' ? 'rounded-lg' : 'rounded-full'}`}
                                />
                            ) : (
                                <div
                                    className={`flex h-16 w-16 items-center justify-center bg-surface text-base font-semibold text-brand ${user?.role === 'employer' ? 'rounded-lg' : 'rounded-full'}`}
                                >
                                    {initials(user?.display_name ?? user?.name ?? 'U')}
                                </div>
                            )}
                            <p className="mt-3 font-semibold text-ink">{user?.display_name ?? user?.name}</p>
                            {user?.ournear_id ? <p className="mt-1 text-xs text-muted">OurNear ID {user.ournear_id}</p> : null}
                            {user &&
                            (!user.email_verified ||
                                (user.role === 'employer' && !user.mobile_verified)) ? (
                                <Link href="/account/settings/security" className="mt-3 inline-block text-xs font-semibold text-accent hover:underline">
                                    Complete verification
                                </Link>
                            ) : null}
                        </div>
                        <AccountNav onNavigate={() => setOpen(false)} />
                    </aside>
                    <div className="min-w-0">{children}</div>
                </PageContainer>
            </section>
            {isCandidate ? <CandidateMobileNav /> : null}
        </PublicLayout>
    );
}
