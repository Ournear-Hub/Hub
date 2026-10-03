import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import EmployerStaffingSearch from '@/Components/Account/Staffing/EmployerStaffingSearch';
import StaffingRequestCard, { type StaffingRequest } from '@/Components/Account/Staffing/StaffingRequestCard';
import Button from '@/Components/Public/Button';
import Card from '@/Components/UI/Card';
import Pagination from '@/Components/Pagination';
import type { Paginator } from '@/types/jobs';

type WorkspaceSummary = {
    total_requests: number;
    active_requests: number;
    completed_requests: number;
    closed_requests: number;
};

type Props = {
    company: {
        name: string;
        business_id: string | null;
    };
    summary: WorkspaceSummary;
    recent_requests: StaffingRequest[];
    enquiries: Paginator<StaffingRequest>;
    filters: {
        q: string;
        status: string;
    };
    status_options: Array<{ value: string; label: string }>;
    request_href: string;
    support_href: string;
};

function detailHref(base: string | undefined, filters: Props['filters']): string | undefined {
    if (!base) {
        return undefined;
    }
    const params = new URLSearchParams();
    if (filters.q) {
        params.set('q', filters.q);
    }
    if (filters.status) {
        params.set('status', filters.status);
    }
    const query = params.toString();
    return query ? `${base}?${query}` : base;
}

export default function Enquiries({
    company,
    summary,
    recent_requests,
    enquiries,
    filters,
    status_options,
    request_href,
    support_href,
}: Props) {
    const empty = enquiries.data.length === 0;
    const hasFilters = Boolean(filters.q || filters.status);
    const noRequests = empty && enquiries.total === 0 && !hasFilters && summary.total_requests === 0;
    const noMatches = empty && !noRequests;

    return (
        <AccountLayout
            title="Staffing Services"
            description="Request and manage manpower support from OurNear."
            breadcrumbs={[
                { label: 'Dashboard', href: '/account' },
                { label: 'Hiring', href: '/account/hiring' },
                { label: 'Staffing Services' },
            ]}
        >
            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <header className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted">Staffing Services</p>
                    <h1 className="mt-1 text-xl font-semibold text-ink sm:text-2xl">{company.name}</h1>
                    {company.business_id ? <p className="mt-1 text-sm text-muted">{company.business_id}</p> : null}
                    <p className="mt-3 text-sm text-muted">
                        Request and track manpower and workforce support from OurNear. This workspace is separate from Direct
                        Hiring / ATS, Recruitment Partnership, and job posting on OurNear Jobs.
                    </p>
                    <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <Button href={request_href} variant="primary" className="min-h-11 w-full sm:w-auto">
                            Request staffing support
                        </Button>
                        <Button href={support_href} variant="secondary" className="min-h-11 w-full sm:w-auto">
                            Contact staffing support
                        </Button>
                        <Link href="/account/hiring" className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                            Hiring Command Center
                        </Link>
                    </div>
                </header>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <Card density="compact" as="article">
                        <p className="text-xs text-muted">Total requests</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.total_requests}</p>
                    </Card>
                    <Card density="compact" as="article">
                        <p className="text-xs text-muted">Active</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.active_requests}</p>
                    </Card>
                    <Card density="compact" as="article">
                        <p className="text-xs text-muted">Completed</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.completed_requests}</p>
                    </Card>
                    <Card density="compact" as="article">
                        <p className="text-xs text-muted">Closed</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.closed_requests}</p>
                    </Card>
                </div>

                {recent_requests.length > 0 && !hasFilters ? (
                    <Card as="section" density="compact">
                        <h2 className="text-base font-semibold text-ink">Recent staffing requests</h2>
                        <div className="mt-3 space-y-3">
                            {recent_requests.map((request) => (
                                <StaffingRequestCard key={request.id} request={request} compact />
                            ))}
                        </div>
                    </Card>
                ) : null}

                {noRequests ? (
                    <div className="rounded-xl border border-dashed border-line bg-white p-8 text-center">
                        <p className="text-sm text-muted">No staffing requests linked to this account yet.</p>
                        <p className="mt-2 text-sm text-muted">Submit a workforce enquiry to start a staffing request with OurNear.</p>
                        <Button href={request_href} variant="secondary" className="mt-4 min-h-11">
                            Request staffing support
                        </Button>
                    </div>
                ) : (
                    <>
                        <EmployerStaffingSearch filters={filters} statusOptions={status_options} resultCount={enquiries.data.length} />

                        {noMatches ? (
                            <div className="rounded-xl border border-dashed border-line bg-white p-8 text-center">
                                <p className="text-sm text-muted">No staffing requests match these filters.</p>
                                <Button href="/account/enquiries" variant="secondary" className="mt-4 min-h-11">
                                    Clear filters
                                </Button>
                            </div>
                        ) : (
                            <section aria-labelledby="staffing-requests">
                                <h2 id="staffing-requests" className="text-base font-semibold text-ink">
                                    Your staffing requests
                                </h2>
                                <p className="mt-1 text-sm text-muted">{enquiries.total} total</p>
                                <div className="mt-3 space-y-3">
                                    {enquiries.data.map((request) => (
                                        <StaffingRequestCard
                                            key={request.id}
                                            request={{
                                                ...request,
                                                detail_href: detailHref(request.detail_href, filters),
                                            }}
                                        />
                                    ))}
                                    <Pagination links={enquiries.links} />
                                </div>
                            </section>
                        )}
                    </>
                )}

                <p className="text-xs text-muted">
                    Internal staff notes and admin workflow stay hidden. You only see staffing requests linked to this employer
                    account.
                </p>
            </div>
        </AccountLayout>
    );
}
