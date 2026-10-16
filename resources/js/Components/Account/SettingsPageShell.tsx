import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import RecruiterSettingsNav from '@/Components/Account/RecruiterSettingsNav';
import SettingsNav from '@/Components/Account/SettingsNav';
import AccountLayout from '@/Layouts/AccountLayout';
import { usesRecruiterAccountChrome } from '@/lib/recruiterAccountPaths';
import type { SharedPageProps } from '@/types/page';

type Props = {
    flash?: ReactNode;
    children: ReactNode;
};

export default function SettingsPageShell({ flash, children }: Props) {
    const { url, props } = usePage<SharedPageProps>();
    const path = url.split('#')[0]?.split('?')[0] ?? url;
    const user = props.auth.user;
    const recruiterChrome = usesRecruiterAccountChrome(
        path,
        user?.role,
        Boolean(user?.employer_identity),
    );

    return (
        <AccountLayout
            title={recruiterChrome ? 'Recruiter Verification' : 'Settings'}
            description={
                recruiterChrome
                    ? 'Verification status and hiring activation requirements.'
                    : 'Manage your account, security and preferences.'
            }
            breadcrumbs={
                recruiterChrome
                    ? [
                          { label: 'Hiring', href: '/account/hiring' },
                          { label: 'Recruiter Verification' },
                      ]
                    : [
                          { label: 'Personal Dashboard', href: '/account' },
                          { label: 'Settings' },
                      ]
            }
        >
            {recruiterChrome ? <RecruiterSettingsNav /> : <SettingsNav />}
            {flash}
            <div className={`mx-auto min-w-0 space-y-3 ${recruiterChrome ? 'max-w-none' : 'max-w-3xl lg:max-w-none'}`}>
                {children}
            </div>
        </AccountLayout>
    );
}
