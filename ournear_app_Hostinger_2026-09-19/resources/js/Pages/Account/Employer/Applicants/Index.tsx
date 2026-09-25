import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';

type Applicant = {
    id: number;
    business_id: string | null;
    name: string;
    photo_url: string | null;
    username: string | null;
    designation: string | null;
    location: string | null;
    experience: string | null;
    qualification: string | null;
    status: string;
    applied_at: string | null;
    has_resume: boolean;
    resume_url: string;
    public_profile_url: string | null;
};

type Props = {
    job: {
        title: string;
        business_id: string | null;
    };
    applications: Applicant[];
};

function initials(name: string) {
    return name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
}

export default function EmployerApplicants({ job, applications }: Props) {
    return (
        <AccountLayout title={`Applicants · ${job.title}`} description={job.business_id ?? undefined}>
            <Link href="/account/jobs" className="mb-5 inline-block text-sm font-medium text-brand">Back to job management</Link>
            {applications.length === 0 ? (
                <p className="rounded-xl border border-dashed border-line bg-white p-8 text-center text-sm text-muted">
                    No applicants for this job yet.
                </p>
            ) : (
                <div className="space-y-4">
                    {applications.map((applicant) => (
                        <article key={applicant.id} className="rounded-xl border border-line bg-white p-5">
                            <div className="flex flex-col gap-4 sm:flex-row">
                                {applicant.photo_url ? (
                                    <img src={applicant.photo_url} alt={`${applicant.name} profile`} className="h-16 w-16 rounded-full object-cover" />
                                ) : (
                                    <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-surface font-semibold text-brand">
                                        {initials(applicant.name)}
                                    </div>
                                )}
                                <div className="min-w-0 flex-1">
                                    <h2 className="text-lg font-semibold text-ink">{applicant.name}</h2>
                                    {applicant.username ? <p className="text-sm text-muted">@{applicant.username}</p> : null}
                                    {applicant.designation ? <p className="mt-1 text-sm font-medium text-ink">{applicant.designation}</p> : null}
                                    <p className="mt-2 text-sm text-muted">
                                        {[applicant.location, applicant.experience, applicant.qualification].filter(Boolean).join(' · ') || 'Professional details not added'}
                                    </p>
                                    <p className="mt-2 text-sm text-muted">
                                        {applicant.status} · Applied {applicant.applied_at ?? '—'}
                                    </p>
                                    <div className="mt-4 flex flex-wrap gap-4 text-sm font-medium">
                                        {applicant.public_profile_url ? <Link href={applicant.public_profile_url} className="text-brand">Open applicant profile</Link> : null}
                                        {applicant.has_resume ? <a href={applicant.resume_url} className="text-brand">Application resume</a> : null}
                                    </div>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            )}
            <p className="mt-6 text-xs text-muted">
                Resume links use the application snapshot. Private Resume Library and Candidate Documents are not exposed.
            </p>
        </AccountLayout>
    );
}
