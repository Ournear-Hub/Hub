import { Link, router, usePage } from '@inertiajs/react';
import AccountStatusBadge from '@/Components/Account/StatusBadge';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import Alert from '@/Components/molecules/Alert';
import AccountPageStack from '@/Components/Account/AccountPageStack';
import AccountLayout from '@/Layouts/AccountLayout';
import { accountLayout } from '@/design-system/classNames';
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
    summary: {
        total: number;
        in_progress: number;
    };
    filters: {
        filter: string;
    };
};

const filterOptions = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'completed', label: 'Completed' },
    { value: 'rejected', label: 'Rejected' },
] as const;

const filterClass = (active: boolean) =>
    `inline-flex min-h-10 items-center rounded-md px-3 py-1.5 text-sm font-medium ${
        active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'
    }`;

function formatUpdatedAt(iso: string | null): string | null {
    if (!iso) {
        return null;
    }

    const updated = new Date(iso);
    if (Number.isNaN(updated.getTime())) {
        return null;
    }

    return updated.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

export default function Applications({ applications, summary, filters }: ApplicationsProps) {
    const page = usePage<SharedPageProps>();
    const flash = page.props.flash;
    const errors = page.props.errors as Record<string, string | undefined> | undefined;
    const activeFilter = filters.filter || 'all';
    const isFiltered = activeFilter !== 'all';
    const hasAnyApplications = summary.total > 0;

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
        <AccountLayout
            title="My Applications"
            description="Track your applications and stay updated on your job search."
            breadcrumbs={[
                { label: 'Personal Dashboard', href: '/account' },
                { label: 'My Applications' },
            ]}
        >
            <AccountPageStack className="max-w-none">
                {flash.success ? (
                    <Alert variant="success">
                        {flash.success}
                    </Alert>
                ) : null}
                {errors?.application ? (
                    <Alert variant="error">
                        {errors.application}
                    </Alert>
                ) : null}

                <div className="grid grid-cols-2 gap-3 sm:max-w-md">
                    <Card padding="compact" className="min-w-0">
                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Total</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.total}</p>
                    </Card>
                    <Card padding="compact" className="min-w-0">
                        <p className="text-xs font-medium uppercase tracking-wide text-muted">In progress</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.in_progress}</p>
                        <p className="mt-0.5 text-xs text-muted">Submitted through interview</p>
                    </Card>
                </div>

                <nav className="flex flex-wrap gap-2" aria-label="Filter applications">
                    {filterOptions.map((option) => {
                        const href =
                            option.value === 'all' ? '/account/applications' : `/account/applications?filter=${option.value}`;
                        const active = activeFilter === option.value;

                        return (
                            <Link key={option.value} href={href} className={filterClass(active)} aria-current={active ? 'true' : undefined}>
                                {option.label}
                            </Link>
                        );
                    })}
                </nav>

                {applications.data.length === 0 ? (
                    <Card padding="compact" className="min-w-0">
                        {!hasAnyApplications ? (
                            <>
                                <h2 className="text-base font-semibold text-ink">No applications yet</h2>
                                <p className={`${accountLayout.headingToBody} text-sm text-muted`}>
                                    When you apply to roles, they will appear here with status updates and a timeline on each
                                    application.
                                </p>
                                <Button surface="public" href="/jobs" variant="primary" className="mt-3 min-h-11">
                                    Browse jobs
                                </Button>
                            </>
                        ) : (
                            <>
                                <h2 className="text-base font-semibold text-ink">No applications match this filter</h2>
                                <p className="mt-2 text-sm text-muted">Try another status or view all applications.</p>
                                {isFiltered ? (
                                    <Link
                                        href="/account/applications"
                                        className="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                                    >
                                        Show all applications
                                    </Link>
                                ) : null}
                            </>
                        )}
                    </Card>
                ) : (
                    <>
                        <ul className="divide-y divide-line rounded-xl border border-line bg-white">
                            {applications.data.map((application) => {
                                const meta = [application.job?.company, application.job?.location].filter(Boolean).join(' · ');

                                return (
                                    <li key={application.id} className={`min-w-0 p-4 ${accountLayout.listDivideRow}`}>
                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div className="min-w-0 flex-1">
                                                <h2 className="text-base font-semibold text-ink">{application.job?.title ?? 'Role'}</h2>
                                                {meta ? <p className="mt-0.5 text-sm text-muted">{meta}</p> : null}
                                                <p className="mt-2 text-sm text-muted">
                                                    {formatAppliedAt(application.applied_at)}
                                                    {application.last_updated_at &&
                                                    application.last_updated_at !== application.applied_at &&
                                                    formatUpdatedAt(application.last_updated_at)
                                                        ? ` · Updated ${formatUpdatedAt(application.last_updated_at)}`
                                                        : null}
                                                </p>
                                                {application.job?.is_closed ? (
                                                    <p className="mt-1 text-xs text-muted">Job closed — application history kept</p>
                                                ) : null}
                                            </div>
                                            <AccountStatusBadge status={application.status} className="shrink-0 self-start" />
                                        </div>
                                        <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                            <Link
                                                href={application.href}
                                                className="inline-flex min-h-10 items-center font-semibold text-brand hover:underline"
                                            >
                                                View details
                                            </Link>
                                            {application.job?.href ? (
                                                <Link
                                                    href={application.job.href}
                                                    className="inline-flex min-h-10 items-center font-semibold text-brand hover:underline"
                                                >
                                                    View job
                                                </Link>
                                            ) : application.job ? (
                                                <span className="text-muted">Job listing unavailable</span>
                                            ) : null}
                                            {application.can_withdraw ? (
                                                <button
                                                    type="button"
                                                    className="inline-flex min-h-10 items-center font-semibold text-danger"
                                                    onClick={() => withdraw(application)}
                                                >
                                                    Withdraw
                                                </button>
                                            ) : null}
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                        <Pagination links={applications.links} />
                    </>
                )}
            </AccountPageStack>
        </AccountLayout>
    );
}
