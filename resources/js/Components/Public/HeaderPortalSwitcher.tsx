import { Link, usePage } from '@inertiajs/react';
import AccountNavItemIcon from '@/Components/Account/AccountNavItemIcon';
import { focusRing } from '@/design-system/classNames';
import type { AuthUser, SharedPageProps } from '@/types/page';

function EmployerAvatar({ name, logoUrl, className }: { name: string; logoUrl: string | null; className: string }) {
    if (logoUrl) {
        return <img src={logoUrl} alt="" className={`${className} rounded-md border border-line object-cover`} />;
    }

    return (
        <span
            aria-hidden="true"
            className={`${className} inline-flex items-center justify-center rounded-md border border-line bg-brand/10 text-xs font-semibold text-brand`}
        >
            {name.charAt(0).toUpperCase()}
        </span>
    );
}

const chipClass = `flex min-h-10 max-w-[11rem] items-center gap-2 rounded-full border border-line bg-white py-1 pl-1 pr-3 hover:bg-surface ${focusRing}`;

export default function HeaderPortalSwitcher() {
    const user = usePage<SharedPageProps>().props.auth.user as AuthUser | null;
    const portals = user?.portal_navigation ?? null;

    if (!portals) {
        return null;
    }

    const { candidate, company } = portals;

    return (
        <>
            <Link
                href={candidate.href}
                className={chipClass}
                aria-label={candidate.label}
                title={candidate.label}
            >
                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand/10 text-brand">
                    <AccountNavItemIcon icon={candidate.icon} variant="sheet" />
                </span>
                <span className="sr-only">{candidate.label}</span>
            </Link>
            <Link
                href={company.href}
                className={chipClass}
                aria-label={
                    company.has_company_profile
                        ? `Recruiter dashboard for ${company.label}`
                        : 'Set up company hiring workspace'
                }
                title={company.has_company_profile ? company.label : 'Hiring workspace setup'}
            >
                {company.has_company_profile ? (
                    <EmployerAvatar name={company.label} logoUrl={company.logo_url} className="h-8 w-8 shrink-0" />
                ) : (
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-line bg-brand/10 text-brand">
                        <AccountNavItemIcon icon={company.icon} variant="sheet" />
                    </span>
                )}
                <span className="truncate text-sm font-medium text-ink">{company.label}</span>
            </Link>
        </>
    );
}
