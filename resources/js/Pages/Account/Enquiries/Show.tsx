import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import StatusBadge from '@/Components/Account/StatusBadge';
import Button from '@/Components/Public/Button';
import Card from '@/Components/UI/Card';
import StaffingRequestCard, { type StaffingRequest } from '@/Components/Account/Staffing/StaffingRequestCard';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';

type Props = {
    company: {
        name: string;
        business_id: string | null;
    };
    enquiry: StaffingRequest;
    list_filters: {
        q: string;
        status: string;
    };
    request_href: string;
    support_href: string;
};

function listHref(filters: Props['list_filters']): string {
    const params = new URLSearchParams();
    if (filters.q) {
        params.set('q', filters.q);
    }
    if (filters.status) {
        params.set('status', filters.status);
    }
    const query = params.toString();
    return `/account/enquiries${query ? `?${query}` : ''}`;
}

export default function EmployerEnquiryShow({ company, enquiry, list_filters, request_href, support_href }: Props) {
    const backHref = listHref(list_filters);

    return (
        <AccountLayout
            title={`Staffing request · ${enquiry.workforce_type ?? enquiry.company_name}`}
            description="Staffing Services request detail"
            breadcrumbs={[
                { label: 'Hiring', href: '/account/hiring' },
                { label: 'Staffing Services', href: backHref },
                { label: enquiry.workforce_type ?? 'Request' },
            ]}
        >
            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <Card as="section" density="compact">
                    <Link href={backHref} className="text-sm font-semibold text-brand hover:underline">
                        ← Back to staffing requests
                    </Link>
                    <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-muted">{company.name}</p>
                    <h1 className="mt-1 text-xl font-semibold text-ink">Staffing request details</h1>
                </Card>

                <StaffingRequestCard request={{ ...enquiry, detail_href: undefined }} />

                <Card as="section" density="compact">
                    <h2 className="text-base font-semibold text-ink">Request information</h2>
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        {enquiry.contact_person ? (
                            <div>
                                <dt className="text-muted">Contact person</dt>
                                <dd className="font-medium text-ink">{enquiry.contact_person}</dd>
                            </div>
                        ) : null}
                        {enquiry.training_required != null ? (
                            <div>
                                <dt className="text-muted">Training required</dt>
                                <dd className="font-medium text-ink">{enquiry.training_required ? 'Yes' : 'No'}</dd>
                            </div>
                        ) : null}
                        <div>
                            <dt className="text-muted">Submitted</dt>
                            <dd className="font-medium text-ink">{enquiry.created_at ? formatAppliedAt(enquiry.created_at) : '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Last updated</dt>
                            <dd className="font-medium text-ink">{enquiry.updated_at ? formatAppliedAt(enquiry.updated_at) : '—'}</dd>
                        </div>
                    </dl>
                    {enquiry.additional_requirements ? (
                        <p className="mt-4 text-sm text-ink">
                            <span className="font-medium text-muted">Additional requirements: </span>
                            {enquiry.additional_requirements}
                        </p>
                    ) : null}
                    <div className="mt-4 flex flex-wrap items-center gap-2">
                        <span className="text-sm text-muted">Current status:</span>
                        <StatusBadge status={enquiry.status} />
                    </div>
                </Card>

                <Card as="section" density="compact" className="bg-surface/40">
                    <h2 className="text-base font-semibold text-ink">Next steps</h2>
                    <p className="mt-2 text-sm text-muted">
                        OurNear staffing operations will follow up on this request. Submit another enquiry or contact us if your
                        requirement has changed.
                    </p>
                    <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <Button href={request_href} variant="secondary" className="min-h-11 w-full sm:w-auto">
                            Request staffing support
                        </Button>
                        <Button href={support_href} variant="link" className="min-h-11">
                            Contact staffing support
                        </Button>
                    </div>
                </Card>
            </div>
        </AccountLayout>
    );
}
