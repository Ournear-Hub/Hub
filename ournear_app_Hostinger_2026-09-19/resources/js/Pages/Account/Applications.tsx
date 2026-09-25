import { Link, router, usePage } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import Pagination from '@/Components/Pagination';
import type { Paginator } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';

type AccountApplicationJob = {
    title: string;
    slug: string;
    company: string | null;
    location: string | null;
    is_closed: boolean;
    status_label: string;
    can_view: boolean;
    href: string | null;
};

type AccountApplication = {
    id: number;
    business_id: string | null;
    status: string;
    applied_at: string | null;
    last_updated_at: string | null;
    has_resume: boolean;
    resume_url: string;
    can_withdraw: boolean;
    withdraw_requires_confirmation: boolean;
    href: string;
    job: AccountApplicationJob | null;
};

type ApplicationsProps = {
    applications: Paginator<AccountApplication>;
    filters: {
        filter: string;
    };
};

const filterOptions = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'completed', label: 'Completed' },
    { value: 'rejected', label: 'Rejected' },
];

export default function Applications({ applications, filters }: ApplicationsProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const activeFilter = filters.filter || 'all';

    const withdraw = (application: AccountApplication) => {
        const message = application.withdraw_requires_confirmation
            ? 'You have been selected. Are you sure you want to withdraw this application?'
            : 'Withdraw this application?';

        if (!window.confirm(message)) {
            return;
        }

        router.patch(`/account/applications/${application.id}/withdraw`);
    };

    return (
        <AccountLayout title="Applications" description="Track applications submitted from this account.">
            {flash.success ? <p className="mb-4 text-sm text-success">{flash.success}</p> : null}
            <div className="mb-4 flex flex-wrap gap-2">
                {filterOptions.map((option) => {
                    const href = option.value === 'all' ? '/account/applications' : `/account/applications?filter=${option.value}`;
                    const active = activeFilter === option.value;

                    return (
                        <Link
                            key={option.value}
                            href={href}
                            className={`rounded-full px-3 py-1 text-sm font-medium ${
                                active ? 'bg-brand text-white' : 'border border-line text-muted hover:text-ink'
                            }`}
                        >
                            {option.label}
                        </Link>
                    );
                })}
            </div>
            {applications.data.length === 0 ? (
                <p className="text-sm text-muted">No applications are linked to this account yet.</p>
            ) : (
                <>
                    <ul className="space-y-4">
                        {applications.data.map((application) => (
                            <li key={application.id} className="rounded-xl border border-line bg-white p-6">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">
                                            {application.business_id ?? `Application #${application.id}`}
                                        </p>
                                        <h2 className="mt-1 text-lg font-semibold text-ink">
                                            {application.job?.title ?? 'Role'}
                                        </h2>
                                        <p className="mt-1 text-sm text-muted">
                                            {[application.job?.company, application.job?.location].filter(Boolean).join(' · ')}
                                        </p>
                                    </div>
                                    <span className="rounded-full bg-surface px-3 py-1 text-sm font-medium text-ink">
                                        {application.status}
                                    </span>
                                </div>
                                <p className="mt-3 text-sm text-muted">
                                    Applied {application.applied_at ?? '—'}
                                    {application.last_updated_at ? ` · Updated ${application.last_updated_at}` : ''}
                                </p>
                                {application.job?.is_closed ? (
                                    <p className="mt-2 text-sm text-muted">This job is closed.</p>
                                ) : null}
                                <div className="mt-4 flex flex-wrap items-center gap-4 text-sm">
                                    <Link href={application.href} className="font-medium text-brand">
                                        Open application
                                    </Link>
                                    {application.job?.href ? (
                                        <Link href={application.job.href} className="font-medium text-brand">
                                            View job
                                        </Link>
                                    ) : application.job?.slug ? (
                                        <span className="text-muted">Job listing unavailable</span>
                                    ) : null}
                                    {application.has_resume ? (
                                        <a href={application.resume_url} className="font-medium text-accent">
                                            Download resume
                                        </a>
                                    ) : null}
                                    {application.can_withdraw ? (
                                        <button
                                            type="button"
                                            className="font-medium text-danger"
                                            onClick={() => withdraw(application)}
                                        >
                                            Withdraw
                                        </button>
                                    ) : null}
                                </div>
                            </li>
                        ))}
                    </ul>
                    <Pagination links={applications.links} />
                </>
            )}
            <p className="mt-6 text-sm">
                <Link href="/account" className="font-medium text-brand hover:underline">
                    Back to account
                </Link>
            </p>
        </AccountLayout>
    );
}
