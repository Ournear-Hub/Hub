import { usePage } from '@inertiajs/react';
import EmployerHiringCommandCenter from '@/Components/Account/Hiring/EmployerHiringCommandCenter';
import HiringActivationStrip from '@/Components/Account/Hiring/HiringActivationStrip';
import HiringEntryOnboarding from '@/Components/Account/Hiring/HiringEntryOnboarding';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountEmployerHiringWorkspace } from '@/types/account';
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
};

export default function Dashboard({ gate, eligibility, workspace }: HiringDashboardProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const canAdminHiring = usePage<SharedPageProps>().props.auth.user?.can_view_hiring === true;
    const showCommandCenter = workspace !== null;

    return (
        <AccountLayout
            title={showCommandCenter ? 'Hiring Command Center' : 'Hiring'}
            description={
                showCommandCenter
                    ? 'Manage jobs, candidates, and hiring activity.'
                    : 'Set up and enter the OurNear hiring workspace.'
            }
            breadcrumbs={[
                { label: 'Account', href: '/account' },
                { label: showCommandCenter ? 'Hiring Command Center' : 'Hiring' },
            ]}
        >
            {showCommandCenter ? (
                <div className="flex flex-col gap-4">
                    <HiringActivationStrip gate={gate} />
                    <EmployerHiringCommandCenter workspace={workspace} canAdminHiring={canAdminHiring} />
                    {!gate.hiring_active ? (
                        <div id="setup">
                            <HiringEntryOnboarding
                                gate={gate}
                                eligibility={eligibility}
                                successMessage={flash.success}
                                canAdminHiring={canAdminHiring}
                            />
                        </div>
                    ) : null}
                </div>
            ) : (
                <HiringEntryOnboarding
                    gate={gate}
                    eligibility={eligibility}
                    successMessage={flash.success}
                    canAdminHiring={canAdminHiring}
                />
            )}
        </AccountLayout>
    );
}
