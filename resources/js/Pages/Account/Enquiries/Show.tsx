import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import StatusBadge from '@/Components/Account/StatusBadge';
import Button from '@/Components/atoms/Button';
import BackNavLink from '@/Components/molecules/BackNavLink';
import Card from '@/Components/atoms/Card';
import StaffingRequestCard, { type StaffingRequest } from '@/Components/Account/Staffing/StaffingRequestCard';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';

type Props = {
    company: {
        name: string;
        business_id: string | null;
    };
    enquiry: StaffingRequest & { fulfilled_joinings_count?: number; vacancies_remaining?: number };
    candidate_submissions?: {
        id: number;
        name: string;
        status_label: string;
        submission_href?: string;
        business_id: string;
    }[];
    list_filters: {
        q: string;
        status: string;
    };
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

export default function EmployerEnquiryShow({ company, enquiry, candidate_submissions = [], list_filters, support_href }: Props) {
    const backHref = listHref(list_filters);

    return (
        <AccountLayout
            title={`Staffing request · ${enquiry.workforce_type ?? enquiry.company_name}`}
            hideShellHeader
        >
            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <Card as="section" padding="compact">
                    <BackNavLink href={backHref} label="Back to staffing requests" className="mb-3" />
                    <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-muted">{company.name}</p>
                    <h1 className="mt-1 text-xl font-semibold text-ink">Staffing request details</h1>
                </Card>

                <StaffingRequestCard request={{ ...enquiry, detail_href: undefined }} />

                {typeof enquiry.fulfilled_joinings_count === 'number' ? (
                    <Card as="section" padding="compact">
                        <h2 className="text-base font-semibold text-ink">Fulfilment</h2>
                        <p className="mt-2 text-sm text-ink">
                            Confirmed joinings:{' '}
                            <span className="font-semibold">{enquiry.fulfilled_joinings_count}</span> of{' '}
                            {enquiry.number_of_people ?? '—'} requested
                            {typeof enquiry.vacancies_remaining === 'number' ? (
                                <span className="text-muted"> · {enquiry.vacancies_remaining} remaining</span>
                            ) : null}
                        </p>
                    </Card>
                ) : null}

                {candidate_submissions.length > 0 ? (
                    <Card as="section" padding="compact">
                        <h2 className="text-base font-semibold text-ink">Candidates submitted by OurNear</h2>
                        <p className="mt-1 text-sm text-muted">These are staffing coordination records — not job applications.</p>
                        <ul className="mt-4 space-y-2">
                            {candidate_submissions.map((row) => (
                                <li key={row.id} className="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span className="font-medium text-ink">{row.name}</span>
                                    <StatusBadge status={row.status_label} />
                                    {row.submission_href ? (
                                        <Link href={row.submission_href} className="text-brand hover:underline">
                                            Review candidate
                                        </Link>
                                    ) : null}
                                </li>
                            ))}
                        </ul>
                    </Card>
                ) : null}

                <Card as="section" padding="compact">
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

                <Card as="section" padding="compact" className="bg-surface/40">
                    <h2 className="text-base font-semibold text-ink">Next steps</h2>
                    <p className="mt-2 text-sm text-muted">
                        OurNear staffing operations will follow up on this request. Submit another enquiry or contact us if your
                        requirement has changed.
                    </p>
                    <div className="mt-4">
                        <Button surface="public" href={support_href} variant="link" className="min-h-11">
                            Contact staffing support
                        </Button>
                    </div>
                </Card>
            </div>
        </AccountLayout>
    );
}
