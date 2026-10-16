import { Link } from '@inertiajs/react';
import StatusBadge from '@/Components/Account/StatusBadge';
import Button from '@/Components/atoms/Button';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';

export type StaffingRequest = {
    id: number;
    company_name: string;
    contact_person?: string | null;
    industry?: string | null;
    status: string;
    status_value?: string;
    project_location: string | null;
    workforce_type: string | null;
    number_of_people?: number | null;
    required_skills?: string | null;
    expected_start_date?: string | null;
    training_required?: boolean | null;
    additional_requirements?: string | null;
    created_at: string | null;
    updated_at?: string | null;
    detail_href?: string;
};

type Props = {
    request: StaffingRequest;
    compact?: boolean;
};

export default function StaffingRequestCard({ request, compact = false }: Props) {
    const title = request.workforce_type ?? 'Staffing request';
    const meta = [request.project_location, request.number_of_people != null ? `${request.number_of_people} people` : null]
        .filter(Boolean)
        .join(' · ');

    if (compact) {
        return (
            <article className="rounded-xl border border-line bg-white p-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0 text-sm">
                        <p className="font-medium text-ink">{title}</p>
                        {meta ? <p className="text-muted">{meta}</p> : null}
                        <div className="mt-1 flex flex-wrap items-center gap-2">
                            <StatusBadge status={request.status} />
                            {request.created_at ? (
                                <span className="text-muted">{formatAppliedAt(request.created_at)}</span>
                            ) : null}
                        </div>
                    </div>
                    {request.detail_href ? (
                        <Link href={request.detail_href} className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                            View
                        </Link>
                    ) : null}
                </div>
            </article>
        );
    }

    return (
        <article className="rounded-xl border border-line bg-white p-4 sm:p-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="text-base font-semibold text-ink sm:text-lg">{title}</h3>
                    {request.company_name ? <p className="mt-1 text-sm text-muted">{request.company_name}</p> : null}
                    {meta ? <p className="mt-1 text-sm text-muted">{meta}</p> : null}
                </div>
                <StatusBadge status={request.status} />
            </div>
            <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                {request.industry ? (
                    <div>
                        <dt className="text-muted">Industry</dt>
                        <dd className="font-medium text-ink">{request.industry}</dd>
                    </div>
                ) : null}
                {request.expected_start_date ? (
                    <div>
                        <dt className="text-muted">Expected start</dt>
                        <dd className="font-medium text-ink">{request.expected_start_date}</dd>
                    </div>
                ) : null}
            </dl>
            {request.required_skills && !compact ? (
                <p className="mt-3 text-sm text-ink">
                    <span className="text-muted">Skills: </span>
                    {request.required_skills}
                </p>
            ) : null}
            <p className="mt-3 text-sm text-muted">
                Submitted {request.created_at ? formatAppliedAt(request.created_at) : '—'}
                {request.updated_at ? ` · Updated ${formatAppliedAt(request.updated_at)}` : ''}
            </p>
            {request.detail_href ? (
                <div className="mt-4">
                    <Button surface="public" href={request.detail_href} variant="primary" className="min-h-11 w-full sm:w-auto">
                        View details
                    </Button>
                </div>
            ) : null}
        </article>
    );
}
