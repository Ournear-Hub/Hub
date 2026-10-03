import StatusBadge from '@/Components/Account/StatusBadge';
import Button from '@/Components/Public/Button';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';

export type RecruiterCandidateSummaryData = {
    id: number;
    name: string;
    photo_url: string | null;
    username: string | null;
    designation: string | null;
    location: string | null;
    experience: string | null;
    qualification: string | null;
    status: string;
    status_label?: string;
    applied_at?: string | null;
    submitted_at?: string | null;
    has_resume: boolean;
    resume_url: string | null;
    public_profile_url: string | null;
    application_href?: string | null;
    submission_href?: string | null;
    requirement_href?: string | null;
    business_id?: string | null;
    job?: {
        title: string;
        slug?: string;
        business_id?: string | null;
    } | null;
    application_contact?: {
        available: boolean;
        email: string | null;
        mobile: string | null;
        source_label: string;
    };
};

type Props = {
    candidate: RecruiterCandidateSummaryData;
    jobTitle?: string;
    mode: 'application' | 'submission';
    profileQuery?: string;
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function profileHref(base: string | null, profileQuery?: string): string | null {
    if (!base) {
        return null;
    }
    if (!profileQuery) {
        return base;
    }
    const joiner = base.includes('?') ? '&' : '?';
    return `${base}${joiner}${profileQuery}`;
}

export default function RecruiterCandidateSummary({ candidate, jobTitle, mode, profileQuery }: Props) {
    const statusLabel = candidate.status_label ?? candidate.status;
    const appliedJob = candidate.job?.title ?? jobTitle ?? 'Role';
    const whenLabel =
        mode === 'submission'
            ? candidate.submitted_at
                ? formatAppliedAt(candidate.submitted_at).replace(/^Applied /, 'Submitted ')
                : 'Submitted recently'
            : formatAppliedAt(candidate.applied_at ?? null);
    const viewProfileHref = profileHref(candidate.public_profile_url, profileQuery);
    const meta = [candidate.location, candidate.experience, candidate.qualification].filter(Boolean).join(' · ');

    return (
        <article className="rounded-xl border border-line bg-white p-4 sm:p-5">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                {candidate.photo_url ? (
                    <img
                        src={candidate.photo_url}
                        alt=""
                        className="h-14 w-14 shrink-0 rounded-full object-cover sm:h-16 sm:w-16"
                    />
                ) : (
                    <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-surface text-sm font-semibold text-brand sm:h-16 sm:w-16">
                        {initials(candidate.name)}
                    </div>
                )}
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="text-base font-semibold text-ink sm:text-lg">{candidate.name}</h2>
                        <StatusBadge status={statusLabel} />
                    </div>
                    {candidate.designation ? (
                        <p className="mt-1 text-sm font-medium text-ink">{candidate.designation}</p>
                    ) : null}
                    {meta ? <p className="mt-1 text-sm text-muted">{meta}</p> : null}
                    <p className="mt-2 text-sm text-muted">
                        {mode === 'submission' ? 'Submitted for' : 'Applied for'}:{' '}
                        <span className="font-medium text-ink">{appliedJob}</span>
                    </p>
                    <p className="mt-1 text-sm text-muted">{whenLabel}</p>
                    {candidate.business_id ? (
                        <p className="mt-1 text-xs text-muted">Application ID {candidate.business_id}</p>
                    ) : null}
                    {mode === 'application' && candidate.application_contact?.available ? (
                        <p className="mt-2 text-xs text-muted">
                            Contact on file — open the application for email/mobile from the apply snapshot.
                        </p>
                    ) : null}
                    {mode === 'application' && !candidate.application_contact?.available && candidate.application_href ? (
                        <p className="mt-2 text-xs text-muted">Contact details appear on the application when provided at apply time.</p>
                    ) : null}
                    <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                        {mode === 'submission' && candidate.submission_href ? (
                            <Button href={candidate.submission_href} variant="primary" className="min-h-11 w-full sm:w-auto">
                                View submission
                            </Button>
                        ) : null}
                        {mode === 'application' && candidate.application_href ? (
                            <Button href={candidate.application_href} variant="primary" className="min-h-11 w-full sm:w-auto">
                                View application
                            </Button>
                        ) : null}
                        {viewProfileHref ? (
                            <Button href={viewProfileHref} variant="secondary" className="min-h-11 w-full sm:w-auto">
                                View profile
                            </Button>
                        ) : (
                            <span className="text-sm text-muted">Public profile not shared</span>
                        )}
                        {candidate.has_resume && candidate.resume_url ? (
                            <a
                                href={`${candidate.resume_url}?disposition=inline`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex min-h-11 items-center justify-center text-sm font-semibold text-brand hover:underline"
                            >
                                View resume
                            </a>
                        ) : (
                            <span className="text-sm text-muted">No application resume</span>
                        )}
                    </div>
                </div>
            </div>
        </article>
    );
}
