import { usePage } from '@inertiajs/react';
import AccountNotificationsBell from '@/Components/Account/AccountNotificationsBell';
import CompanyWorkspaceMenu from '@/Components/Account/CompanyWorkspaceMenu';
import DashboardContextualSearch from '@/Components/Account/DashboardContextualSearch';
import PageContainer from '@/Components/Public/PageContainer';
import type { SharedPageProps } from '@/types/page';

/** Sticky recruiter workspace band below the public site header (contextual search + account controls). */
export default function RecruiterWorkspaceHeader() {
    const user = usePage<SharedPageProps>().props.auth.user;

    if (!user) {
        return null;
    }

    return (
        <section
            className="resume-print-hide sticky top-16 z-30 border-b border-line bg-white/95 backdrop-blur-[2px] sm:top-[4.5rem]"
            aria-label="Company workspace"
        >
            <PageContainer
                width="wide"
                gutter="account"
                className="grid grid-cols-1 items-center gap-2 py-2.5 max-sm:grid-rows-[auto_auto] sm:grid-cols-[minmax(0,1fr)_min(100%,520px)_minmax(0,1fr)] sm:gap-3 sm:py-3"
            >
                <div className="hidden min-w-0 sm:block" aria-hidden="true" />

                <div className="order-2 min-w-0 w-full max-sm:max-w-none sm:order-none sm:col-start-2 sm:w-full sm:max-w-[520px] sm:justify-self-center">
                    <DashboardContextualSearch
                        scope="employer"
                        label="Search your jobs and applicants"
                        variant="inline"
                    />
                </div>

                <div className="order-1 flex shrink-0 items-center justify-end gap-2 sm:order-none sm:col-start-3 sm:row-start-1">
                    <AccountNotificationsBell />
                    {user.employer_identity ? (
                        <CompanyWorkspaceMenu identity={user.employer_identity} className="inline-flex" iconOnly />
                    ) : null}
                </div>
            </PageContainer>
        </section>
    );
}
