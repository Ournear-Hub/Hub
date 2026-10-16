import { router } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import EmployerStaffingSearch from '@/Components/Account/Staffing/EmployerStaffingSearch';
import StaffingRequestCard, { type StaffingRequest } from '@/Components/Account/Staffing/StaffingRequestCard';
import StaffingRequirementsForm, {
    type StaffingFormDefaults,
} from '@/Components/Account/Staffing/StaffingRequirementsForm';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
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
    staffing_form: {
        industries: string[];
        workforce_types: string[];
        defaults: StaffingFormDefaults;
    };
    staffing_access: {
        status: string;
        label: string;
        can_submit_requirement: boolean;
        can_request_access: boolean;
    };
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
    staffing_form,
    staffing_access,
}: Props) {
    const empty = enquiries.data.length === 0;
    const hasFilters = Boolean(filters.q || filters.status);
    const noRequests = empty && enquiries.total === 0 && !hasFilters && summary.total_requests === 0;
    const noMatches = empty && !noRequests;

    return (
        <AccountLayout title="Staffing" hideShellHeader>
            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <div>
                    <h1 className="text-xl font-semibold text-ink sm:text-2xl">Staffing</h1>
                    <p className="mt-1 text-sm text-muted">
                        {company.name}
                        {company.business_id ? ` · ${company.business_id}` : ''}
                    </p>
                </div>

                <Card padding="standard">
                    <p className="text-sm font-semibold text-ink">Staffing module access</p>
                    <p className="mt-1 text-sm text-muted">Status: {staffing_access.label}</p>
                    {staffing_access.can_request_access ? (
                        <Button
                            surface="public"
                            type="button"
                            variant="primary"
                            className="mt-3"
                            onClick={() => router.post('/account/company/staffing-access/request')}
                        >
                            Request Staffing access
                        </Button>
                    ) : null}
                    {!staffing_access.can_submit_requirement ? (
                        <p className="mt-2 text-sm text-muted">
                            Staffing requirements can be submitted after admin approves Staffing access. This is separate
                            from company verification and from Hiring job posting.
                        </p>
                    ) : null}
                </Card>

                {staffing_access.can_submit_requirement ? (
                    <StaffingRequirementsForm
                        industries={staffing_form.industries}
                        workforce_types={staffing_form.workforce_types}
                        defaults={staffing_form.defaults}
                    />
                ) : null}

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <Card padding="compact" as="article">
                        <p className="text-xs text-muted">Total requests</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.total_requests}</p>
                    </Card>
                    <Card padding="compact" as="article">
                        <p className="text-xs text-muted">Active</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.active_requests}</p>
                    </Card>
                    <Card padding="compact" as="article">
                        <p className="text-xs text-muted">Completed</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.completed_requests}</p>
                    </Card>
                    <Card padding="compact" as="article">
                        <p className="text-xs text-muted">Closed</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.closed_requests}</p>
                    </Card>
                </div>

                {recent_requests.length > 0 && !hasFilters ? (
                    <Card as="section" padding="compact">
                        <h2 className="text-base font-semibold text-ink">Recent staffing requests</h2>
                        <div className="mt-3 space-y-3">
                            {recent_requests.map((request) => (
                                <StaffingRequestCard key={request.id} request={request} compact />
                            ))}
                        </div>
                    </Card>
                ) : null}

                {noRequests ? (
                    <div className="rounded-xl border border-dashed border-line bg-white p-6 text-center">
                        <p className="text-sm text-muted">
                            No staffing requests on file yet. Submit the form above and your requests will appear here
                            after OurNear processes them.
                        </p>
                    </div>
                ) : (
                    <>
                        <EmployerStaffingSearch filters={filters} statusOptions={status_options} resultCount={enquiries.data.length} />

                        {noMatches ? (
                            <div className="rounded-xl border border-dashed border-line bg-white p-8 text-center">
                                <p className="text-sm text-muted">No staffing requests match these filters.</p>
                                <Button surface="public" href="/account/enquiries" variant="secondary" className="mt-4 min-h-11">
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
