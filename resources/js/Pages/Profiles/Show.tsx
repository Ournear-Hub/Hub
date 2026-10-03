import { Link } from '@inertiajs/react';
import { useMemo } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import Breadcrumbs from '@/Components/Navigation/Breadcrumbs';
import ProfileShell from '@/Components/Profile/ProfileShell';
import PageContainer from '@/Components/Public/PageContainer';
import Button from '@/Components/Public/Button';
import StatusBadge from '@/Components/Account/StatusBadge';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';
import type { SiteContent } from '@/types/public';

type Props = {
    content: SiteContent;
    viewer?: {
        is_owner?: boolean;
    };
    profile: {
        name: string;
        username: string | null;
        business_id?: string | null;
        photo_url: string | null;
        headline?: string | null;
        location?: string | null;
        bio: string | null;
        designation: string | null;
        current_company: string | null;
        industry: string | null;
        functional_area: string | null;
        experience: string | null;
        qualification: string | null;
        verifications?: {
            email: boolean;
            mobile: boolean;
            whatsapp: boolean;
        };
        skills: string[];
        employment_history: Array<{
            job_title: string | null;
            company_name: string | null;
            industry: string | null;
            is_current: boolean;
        }>;
        educations?: Array<{
            institution: string;
            qualification: string | null;
            field: string | null;
        }>;
        projects?: Array<{
            name: string;
            role: string | null;
            company: string | null;
            description: string | null;
        }>;
        certifications?: Array<{
            name: string;
            issuer: string | null;
            issued_on: string | null;
            expires_on: string | null;
        }>;
        languages?: Array<{
            name: string;
            proficiency: string | null;
        }>;
    };
};

export default function PublicCandidateProfile({ profile, viewer }: Props) {
    const isOwner = viewer?.is_owner === true;
    const subtitle =
        profile.headline || [profile.designation, profile.current_company].filter(Boolean).join(' · ') || null;

    const hiringContext = useMemo(() => {
        if (typeof window === 'undefined') {
            return null;
        }
        const params = new URLSearchParams(window.location.search);
        const back = params.get('back');
        if (!back || !back.startsWith('/account/')) {
            return null;
        }
        return {
            back,
            jobTitle: params.get('job_title'),
            applicationStatus: params.get('application_status'),
            appliedAt: params.get('applied_at'),
            applicationHref: params.get('application_href'),
        };
    }, []);

    return (
        <PublicLayout title={profile.name}>
            <section className="py-10">
                <PageContainer className="max-w-4xl">
                    {hiringContext ? (
                        <div className="mb-4 rounded-xl border border-line bg-white p-4 sm:p-5">
                            <p className="text-xs font-semibold uppercase tracking-wide text-muted">Applicant for</p>
                            {hiringContext.jobTitle ? (
                                <p className="mt-1 text-base font-semibold text-ink">{hiringContext.jobTitle}</p>
                            ) : null}
                            {hiringContext.applicationStatus ? (
                                <div className="mt-2 flex flex-wrap items-center gap-2 text-sm">
                                    <span className="text-muted">Application status:</span>
                                    <StatusBadge status={hiringContext.applicationStatus} />
                                </div>
                            ) : null}
                            {hiringContext.appliedAt ? (
                                <p className="mt-2 text-sm text-muted">{formatAppliedAt(hiringContext.appliedAt)}</p>
                            ) : null}
                            <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                {hiringContext.applicationHref ? (
                                    <Button href={hiringContext.applicationHref} variant="secondary" className="min-h-11">
                                        Back to application
                                    </Button>
                                ) : null}
                                <Button href={hiringContext.back} variant="link" className="min-h-11">
                                    Back to applicants
                                </Button>
                            </div>
                        </div>
                    ) : null}
                    <Breadcrumbs
                        items={[
                            { label: 'Home', href: '/' },
                            { label: 'Profiles' },
                            { label: profile.name },
                        ]}
                    />
                    <ProfileShell
                        name={profile.name}
                        photoUrl={profile.photo_url}
                        profileHref={profile.username ? `/profiles/${profile.username}` : null}
                        businessId={profile.business_id}
                        subtitle={subtitle}
                        location={profile.location}
                        verifications={isOwner ? profile.verifications : undefined}
                        headerVariant="compact"
                        heading="h1"
                        actions={
                            isOwner ? (
                                <Link
                                    href="/account/profile"
                                    className="inline-flex min-h-11 items-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white"
                                >
                                    Edit profile
                                </Link>
                            ) : null
                        }
                    >
                        <article className="rounded-xl border border-line bg-white p-6 sm:p-8">
                            {profile.bio ? (
                                <section>
                                    <h2 className="text-lg font-semibold text-ink">About</h2>
                                    <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{profile.bio}</p>
                                </section>
                            ) : null}
                            <dl className="mt-8 grid gap-4 sm:grid-cols-2">
                                {[
                                    ['Industry', profile.industry],
                                    ['Functional area', profile.functional_area],
                                    ['Experience', profile.experience],
                                    ['Qualification', profile.qualification],
                                ]
                                    .filter(([, value]) => value)
                                    .map(([label, value]) => (
                                        <div key={label}>
                                            <dt className="text-xs font-semibold uppercase tracking-wide text-muted">{label}</dt>
                                            <dd className="mt-1 text-sm text-ink">{value}</dd>
                                        </div>
                                    ))}
                            </dl>
                            {profile.skills.length > 0 ? (
                                <section className="mt-8">
                                    <h2 className="text-lg font-semibold text-ink">Skills</h2>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {profile.skills.map((skill) => (
                                            <span key={skill} className="rounded-full bg-surface px-3 py-1 text-sm text-ink">
                                                {skill}
                                            </span>
                                        ))}
                                    </div>
                                </section>
                            ) : null}
                            {profile.employment_history.length > 0 ? (
                                <section className="mt-8">
                                    <h2 className="text-lg font-semibold text-ink">Experience</h2>
                                    <div className="mt-3 space-y-3">
                                        {profile.employment_history.map((history, index) => (
                                            <div key={`${history.company_name}-${history.job_title}-${index}`} className="rounded-lg bg-surface p-4">
                                                <p className="font-medium text-ink">{history.job_title || 'Role'}</p>
                                                <p className="mt-1 text-sm text-muted">
                                                    {[history.company_name, history.industry, history.is_current ? 'Current' : null]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </section>
                            ) : null}
                            {(profile.educations ?? []).length > 0 ? (
                                <section className="mt-8">
                                    <h2 className="text-lg font-semibold text-ink">Education</h2>
                                    <ul className="mt-3 space-y-2 text-sm text-ink">
                                        {(profile.educations ?? []).map((item, index) => (
                                            <li key={`${item.institution}-${index}`}>
                                                {item.institution}
                                                {item.qualification ? ` · ${item.qualification}` : ''}
                                                {item.field ? ` · ${item.field}` : ''}
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ) : null}
                            {(profile.projects ?? []).length > 0 ? (
                                <section className="mt-8">
                                    <h2 className="text-lg font-semibold text-ink">Projects</h2>
                                    <ul className="mt-3 space-y-3 text-sm text-ink">
                                        {(profile.projects ?? []).map((item, index) => (
                                            <li key={`${item.name}-${index}`} className="rounded-lg bg-surface p-4">
                                                <p className="font-medium">{item.name}</p>
                                                <p className="mt-1 text-muted">
                                                    {[item.role, item.company].filter(Boolean).join(' · ')}
                                                </p>
                                                {item.description ? (
                                                    <p className="mt-2 whitespace-pre-line text-muted">{item.description}</p>
                                                ) : null}
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ) : null}
                            {(profile.certifications ?? []).length > 0 ? (
                                <section className="mt-8">
                                    <h2 className="text-lg font-semibold text-ink">Certifications</h2>
                                    <ul className="mt-3 space-y-2 text-sm text-ink">
                                        {(profile.certifications ?? []).map((item, index) => (
                                            <li key={`${item.name}-${index}`}>
                                                {item.name}
                                                {item.issuer ? ` · ${item.issuer}` : ''}
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ) : null}
                            {(profile.languages ?? []).length > 0 ? (
                                <section className="mt-8">
                                    <h2 className="text-lg font-semibold text-ink">Languages</h2>
                                    <ul className="mt-3 space-y-2 text-sm text-ink">
                                        {(profile.languages ?? []).map((item, index) => (
                                            <li key={`${item.name}-${index}`}>
                                                {item.name}
                                                {item.proficiency ? ` · ${item.proficiency}` : ''}
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ) : null}
                        </article>
                    </ProfileShell>
                    <p className="mt-4 text-xs text-muted">
                        Private resumes, documents, KYC, and account details are never included on this public profile.
                    </p>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
