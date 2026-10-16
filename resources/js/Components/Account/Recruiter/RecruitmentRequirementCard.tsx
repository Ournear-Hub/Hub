import { Link } from '@inertiajs/react';
import StatusBadge from '@/Components/Account/StatusBadge';
import Button from '@/Components/atoms/Button';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';

export type RecruitmentRequirement = {
    job_id: number;
    title: string;
    slug: string;
    business_id: string | null;
    location: string | null;
    vacancy: number | null;
    created_at: string | null;
    job_status_label?: string | null;
    submission_count: number;
    status_label: string | null;
    latest_submitted_at: string | null;
    href: string;
    next_action: string | null;
};

type Props = {
    requirement: RecruitmentRequirement;
};

export default function RecruitmentRequirementCard({ requirement }: Props) {
    const meta = [
        requirement.location,
        requirement.vacancy != null ? `${requirement.vacancy} opening${requirement.vacancy === 1 ? '' : 's'}` : null,
        requirement.business_id,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <article className="rounded-xl border border-line bg-white p-4 sm:p-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <h3 className="text-base font-semibold text-ink sm:text-lg">{requirement.title}</h3>
                    {meta ? <p className="mt-1 text-sm text-muted">{meta}</p> : null}
                    {requirement.created_at ? (
                        <p className="mt-1 text-sm text-muted">Role posted {formatAppliedAt(requirement.created_at)}</p>
                    ) : null}
                </div>
                {requirement.status_label ? <StatusBadge status={requirement.status_label} /> : null}
            </div>
            <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt className="text-muted">Candidates submitted</dt>
                    <dd className="font-semibold text-ink">{requirement.submission_count}</dd>
                </div>
                {requirement.job_status_label ? (
                    <div>
                        <dt className="text-muted">Job listing</dt>
                        <dd className="font-medium text-ink">{requirement.job_status_label}</dd>
                    </div>
                ) : null}
                {requirement.latest_submitted_at ? (
                    <div className="sm:col-span-2">
                        <dt className="text-muted">Latest submission</dt>
                        <dd className="font-medium text-ink">{formatAppliedAt(requirement.latest_submitted_at)}</dd>
                    </div>
                ) : null}
            </dl>
            {requirement.next_action ? <p className="mt-3 text-sm text-muted">{requirement.next_action}</p> : null}
            <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <Button surface="public" href={requirement.href} variant="primary" className="min-h-11 w-full sm:w-auto">
                    View requirement
                </Button>
                <Link href={requirement.href} className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                    Open submissions
                </Link>
            </div>
        </article>
    );
}
