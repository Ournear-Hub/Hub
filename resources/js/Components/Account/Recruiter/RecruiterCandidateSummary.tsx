import Button from '@/Components/atoms/Button';
import ApplicantSummaryCard from '@/Components/organisms/ApplicantSummaryCard';
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
    ats_stage?: string;
    ats_stage_label?: string;
    pipeline_stage_label?: string;
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
    const stageLine = candidate.ats_stage_label ?? candidate.pipeline_stage_label;
    const appliedJob = candidate.job?.title ?? jobTitle ?? 'Role';
    const whenLabel =
        mode === 'submission'
            ? candidate.submitted_at
                ? formatAppliedAt(candidate.submitted_at).replace(/^Applied /, 'Submitted ')
                : 'Submitted recently'
            : formatAppliedAt(candidate.applied_at ?? null);
    const viewProfileHref = profileHref(candidate.public_profile_url, profileQuery);
    const meta = [candidate.location, candidate.experience, candidate.qualification, stageLine ? `Stage: ${stageLine}` : '']
        .filter(Boolean)
        .join(' · ');

    const notices =
        mode === 'application' && candidate.application_contact?.available ? (
            <p>Contact on file — open the application for email/mobile from the apply snapshot.</p>
        ) : mode === 'application' && !candidate.application_contact?.available && candidate.application_href ? (
            <p>Contact details appear on the application when provided at apply time.</p>
        ) : null;

    const actions = (
        <>
            {mode === 'submission' && candidate.submission_href ? (
                <Button surface="public" href={candidate.submission_href} variant="primary" className="min-h-11 w-full sm:w-auto">
                    View submission
                </Button>
            ) : null}
            {mode === 'application' && candidate.application_href ? (
                <Button surface="public" href={candidate.application_href} variant="primary" className="min-h-11 w-full sm:w-auto">
                    View application
                </Button>
            ) : null}
            {viewProfileHref ? (
                <Button surface="public" href={viewProfileHref} variant="secondary" className="min-h-11 w-full sm:w-auto">
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
        </>
    );

    return (
        <ApplicantSummaryCard
            name={candidate.name}
            statusLabel={statusLabel}
            photoUrl={candidate.photo_url}
            initials={initials(candidate.name)}
            designation={candidate.designation}
            metaLine={meta || null}
            contextPrefix={mode === 'submission' ? 'Submitted for' : 'Applied for'}
            contextTitle={appliedJob}
            timeLine={whenLabel}
            referenceLine={candidate.business_id ? `Application ID ${candidate.business_id}` : null}
            notices={notices}
            actions={actions}
        />
    );
}
