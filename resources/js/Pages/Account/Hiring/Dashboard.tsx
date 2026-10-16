import { usePage } from '@inertiajs/react';
import CompanyWorkspaceOverview from '@/Components/Account/CompanyWorkspaceOverview';
import HiringEntryOnboarding from '@/Components/Account/Hiring/HiringEntryOnboarding';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountEmployerHiringWorkspace, AccountLookupOption } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type Gate = {
    email_verified: boolean;
    mobile_verified: boolean;
    whatsapp_required: boolean;
    is_employer: boolean;
    has_company?: boolean;
    company_name?: string | null;
    hiring_email?: string;
    hiring_email_verified?: boolean;
    uses_account_email?: boolean;
    hiring_active: boolean;
    workspace_ready?: boolean;
    unlimited_publishing?: boolean;
    trial_available?: boolean;
    trial_active?: boolean;
    trial_expired?: boolean;
    trial_expires_at?: string | null;
    company_phone?: string | null;
    industry_id?: number | null;
    message: string;
};

type Eligibility = {
    eligible: boolean;
    reason: string | null;
    next_action: string | null;
} | null;

type HiringDashboardProps = {
    gate: Gate;
    eligibility: Eligibility;
    workspace: AccountEmployerHiringWorkspace | null;
    industries: AccountLookupOption[];
};

const companyWorkspaceSubtitle =
    'Manage recruitment, job postings, applicants, and hiring pipeline activity for your company.';

export default function Dashboard({ gate, eligibility, workspace, industries }: HiringDashboardProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const canAdminHiring = usePage<SharedPageProps>().props.auth.user?.can_view_hiring === true;
    const showRecruiterDashboard = workspace !== null;

    return (
        <AccountLayout
            title={showRecruiterDashboard ? 'Company Workspace' : 'Hiring'}
            description={
                showRecruiterDashboard ? companyWorkspaceSubtitle : 'Set up and enter the OurNear hiring workspace.'
            }
            breadcrumbs={
                showRecruiterDashboard
                    ? [{ label: 'Company Workspace' }]
                    : [
                          { label: 'Account', href: '/account' },
                          { label: 'Hiring' },
                      ]
            }
        >
            {showRecruiterDashboard ? (
                <CompanyWorkspaceOverview workspace={workspace as AccountEmployerHiringWorkspace} gate={gate} />
            ) : (
                <HiringEntryOnboarding
                    gate={gate}
                    eligibility={eligibility}
                    industries={industries}
                    successMessage={flash.success}
                    canAdminHiring={canAdminHiring}
                />
            )}
        </AccountLayout>
    );
}
