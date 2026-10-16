import { useState, type ReactNode } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AccountNav from '@/Components/Account/AccountNav';
import CandidateSidebarProfileCard from '@/Components/Account/CandidateSidebarProfileCard';
import EmployerSidebarIdentityCard from '@/Components/Account/EmployerSidebarIdentityCard';
import CandidateMobileNav, { candidateMobileNavSpacerClass } from '@/Components/Account/CandidateMobileNav';
import Breadcrumbs, { type BreadcrumbItem } from '@/Components/Navigation/Breadcrumbs';
import RecruiterWorkspaceHeader from '@/Components/Account/RecruiterWorkspaceHeader';
import EmailVerificationReminderModal from '@/Components/Account/EmailVerificationReminderModal';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import { accountLayout } from '@/design-system/classNames';
import { isRecruiterAccountPath, usesRecruiterAccountChrome } from '@/lib/recruiterAccountPaths';
import type { SharedPageProps } from '@/types/page';

type AccountLayoutProps = {
    title: string;
    description?: string;
    action?: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    /** When true, omit OurNear ID from the sidebar identity card (e.g. Personal Profile). */
    hideSidebarOurnearId?: boolean;
    /** Tighter page title bar (personal dashboard). */
    compactHeader?: boolean;
    /** Hide sidebar photo/name card (personal dashboard shows identity in main content). */
    hideSidebarIdentity?: boolean;
    /** Omit duplicate shell title bar (recruiter pages with in-content headings). */
    hideShellHeader?: boolean;
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

export default function AccountLayout({
    title,
    description,
    action,
    breadcrumbs,
    hideSidebarOurnearId = false,
    compactHeader = false,
    hideSidebarIdentity = false,
    hideShellHeader = false,
    children,
}: AccountLayoutProps) {
    const inertiaPage = usePage<SharedPageProps>();
    const page = inertiaPage.props;
    const user = page.auth.user;
    const [open, setOpen] = useState(false);
    const isCandidate = user?.role === 'candidate';
    const accountPath = inertiaPage.url.split('#')[0]?.split('?')[0] ?? inertiaPage.url;
    const hideOurnearIdSidebar =
        hideSidebarOurnearId || (isCandidate && !accountPath.startsWith('/account/settings'));
    const onRecruiterAccountPath = isRecruiterAccountPath(accountPath);
    const recruiterChrome = usesRecruiterAccountChrome(
        accountPath,
        user?.role,
        Boolean(user?.employer_identity),
    );
    const employerSidebar = recruiterChrome;
    const candidatePersonalSidebar = isCandidate && !recruiterChrome;
    const candidateSidebarProfile = page.candidateSidebarProfile;
    const hideRecruiterOverviewTitle =
        recruiterChrome && (title === 'Dashboard' || title === 'Company Workspace') && !action;
    const showPageTitleBar =
        !recruiterChrome && !hideShellHeader && (!hideRecruiterOverviewTitle || !employerSidebar);
    const showVerificationPrompt =
        Boolean(user) &&
        (!user!.email_verified || (user!.role === 'employer' && !user!.mobile_verified));

    return (
        <PublicLayout title={title}>
            <Head title={title} />
            {recruiterChrome ? <RecruiterWorkspaceHeader /> : null}
            {recruiterChrome && hideRecruiterOverviewTitle ? (
                <div className="resume-print-hide border-b border-line bg-white lg:hidden">
                    <PageContainer width="wide" gutter="account" className="flex justify-end py-2">
                        <button
                            type="button"
                            className="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink"
                            aria-expanded={open}
                            onClick={() => setOpen((value) => !value)}
                        >
                            Menu
                        </button>
                    </PageContainer>
                </div>
            ) : null}
            {showPageTitleBar ? (
                <section className="resume-print-hide border-b border-line bg-white">
                    <PageContainer
                        width="wide"
                        gutter="account"
                        className={`flex flex-wrap items-center justify-between gap-2 sm:gap-3 ${accountLayout.pageTitleBar}`}
                    >
                        <div className="min-w-0 flex-1">
                            {breadcrumbs && breadcrumbs.length > 0 ? (
                                <div className="hidden sm:block">
                                    <Breadcrumbs items={breadcrumbs} />
                                </div>
                            ) : null}
                            <h1
                                className={`truncate font-semibold text-ink ${compactHeader ? 'text-lg sm:text-xl' : 'text-xl sm:text-2xl'}`}
                            >
                                {title}
                            </h1>
                            {description && !recruiterChrome ? (
                                <p className="mt-1 hidden text-sm text-muted sm:block">{description}</p>
                            ) : null}
                        </div>
                        <div className="flex shrink-0 items-center gap-2 sm:gap-3">
                            {action}
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
            ) : null}
            <section
                className={`${accountLayout.mainPy} ${isCandidate && !onRecruiterAccountPath ? candidateMobileNavSpacerClass : ''}`}
            >
                <PageContainer width="wide" gutter="account" className={accountLayout.shellGrid}>
                    <aside className={`resume-print-hide ${isCandidate && !recruiterChrome ? 'hidden lg:block' : open ? 'block' : 'hidden lg:block'}`}>
                        {candidatePersonalSidebar && candidateSidebarProfile ? (
                            <div className="overflow-hidden rounded-xl border border-line bg-white shadow-sm">
                                <CandidateSidebarProfileCard profile={candidateSidebarProfile} />
                                <div className="border-t border-line">
                                    <AccountNav onNavigate={() => setOpen(false)} candidateSidebar />
                                </div>
                            </div>
                        ) : recruiterChrome && user?.employer_identity ? (
                            <div className="overflow-hidden rounded-xl border border-[#214449] bg-[#214449] shadow-sm">
                                <EmployerSidebarIdentityCard identity={user.employer_identity} />
                                <AccountNav
                                    onNavigate={() => setOpen(false)}
                                    employerSidebar
                                    integratedEmployerIdentity
                                />
                            </div>
                        ) : (
                            <>
                                {!hideSidebarIdentity && !recruiterChrome ? (
                                    <div className={accountLayout.sidebarIdentity}>
                                        {user?.photo_url ? (
                                            <img
                                                src={user.photo_url}
                                                alt=""
                                                className="h-16 w-16 rounded-full object-cover object-center"
                                            />
                                        ) : (
                                            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-surface text-base font-semibold text-brand">
                                                {initials(user?.name ?? 'U')}
                                            </div>
                                        )}
                                        <p className="mt-3 font-semibold text-ink">{user?.name}</p>
                                        {user?.ournear_id && !hideOurnearIdSidebar ? (
                                            <p className="mt-1 text-xs text-muted">OurNear ID {user.ournear_id}</p>
                                        ) : null}
                                        {showVerificationPrompt ? (
                                            <Link
                                                href="/account/settings/verification"
                                                className="mt-3 inline-block text-xs font-semibold text-accent hover:underline"
                                            >
                                                Complete verification
                                            </Link>
                                        ) : null}
                                    </div>
                                ) : null}
                                <AccountNav onNavigate={() => setOpen(false)} employerSidebar={employerSidebar} />
                            </>
                        )}
                    </aside>
                    <div className="min-w-0">{children}</div>
                </PageContainer>
            </section>
            {isCandidate && !onRecruiterAccountPath ? <CandidateMobileNav /> : null}
            <EmailVerificationReminderModal />
        </PublicLayout>
    );
}
