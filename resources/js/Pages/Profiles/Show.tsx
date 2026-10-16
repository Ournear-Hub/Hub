import { Link } from '@inertiajs/react';
import { useMemo, type ReactNode } from 'react';
import EmploymentTimeline from '@/Components/Account/EmploymentTimeline';
import Avatar from '@/Components/atoms/Avatar';
import PublicLayout from '@/Layouts/PublicLayout';
import AccountLayout from '@/Layouts/AccountLayout';
import Breadcrumbs from '@/Components/Navigation/Breadcrumbs';
import PageContainer from '@/Components/Public/PageContainer';
import BackNavLink from '@/Components/molecules/BackNavLink';
import PublicShareMenu from '@/Components/Public/PublicShareMenu';
import StatusBadge from '@/Components/Account/StatusBadge';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';
import { accountLayout } from '@/design-system/classNames';
import type { AccountEmploymentHistory } from '@/types/account';
import type { SiteContent } from '@/types/public';

type PublicEmploymentHistory = {
    id?: number;
    company?: string | null;
    designation?: string | null;
    industry?: string | null;
    started_on?: string | null;
    ended_on?: string | null;
    is_current?: boolean;
    is_internship?: boolean;
    description?: string | null;
    job_title?: string | null;
    company_name?: string | null;
};

type Props = {
    content: SiteContent;
    viewer?: {
        is_owner?: boolean;
    };
    profile: {
        name: string;
        username: string | null;
        photo_url: string | null;
        headline?: string | null;
        location?: string | null;
        bio: string | null;
        designation: string | null;
        current_company: string | null;
        experience: string | null;
        qualification: string | null;
        verifications?: {
            email: boolean;
            mobile: boolean;
            whatsapp: boolean;
        };
        skills?: string[];
        employment_history?: PublicEmploymentHistory[];
        educations?: Array<{
            institution: string;
            qualification: string | null;
            field: string | null;
            started_on?: string | null;
            ended_on?: string | null;
        }>;
        projects?: Array<{
            name: string;
            role: string | null;
            company: string | null;
            description: string | null;
            started_on?: string | null;
            ended_on?: string | null;
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
        share_enabled?: boolean;
        share_url?: string | null;
    };
};

function SectionCard({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-xl border border-line bg-white p-5 sm:p-6">
            <h2 className="text-base font-semibold text-ink">{title}</h2>
            <div className="mt-4">{children}</div>
        </section>
    );
}

function formatEducationDates(started?: string | null, ended?: string | null): string | null {
    const parts = [started, ended].filter(Boolean);
    return parts.length > 0 ? parts.join(' – ') : null;
}

function mapEmploymentHistory(items: PublicEmploymentHistory[]): AccountEmploymentHistory[] {
    return items.map((item, index) => ({
        id: item.id ?? index,
        company: item.company ?? item.company_name ?? '',
        designation: item.designation ?? item.job_title ?? '',
        industry_id: null,
        industry: item.industry ?? null,
        started_on: item.started_on ?? null,
        ended_on: item.ended_on ?? null,
        is_current: item.is_current ?? false,
        is_internship: item.is_internship ?? false,
        description: item.description ?? null,
    }));
}

export default function PublicCandidateProfile({ profile, viewer }: Props) {
    const isOwner = viewer?.is_owner === true;
    const skills = profile.skills ?? [];
    const employmentHistory = mapEmploymentHistory(profile.employment_history ?? []);
    const shareEnabled = profile.share_enabled !== false;

    const headerSubtitle =
        profile.headline ||
        [profile.designation, profile.current_company].filter(Boolean).join(' · ') ||
        null;

    const summaryHeadline = profile.headline?.trim() || null;
    const summaryBio = profile.bio?.trim() || null;
    const showSummary = Boolean(summaryHeadline || summaryBio);

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

    const ownerDashboardPreview = isOwner && !hiringContext;

    const previewBody = (
        <div className={`min-w-0 space-y-4 ${ownerDashboardPreview ? accountLayout.profileWorkspace : ''}`}>
            {ownerDashboardPreview ? (
                <Link
                    href="/account/profile"
                    className="inline-flex min-h-10 items-center text-sm font-semibold text-brand hover:underline"
                >
                    ← Back to profile
                </Link>
            ) : null}

            {hiringContext ? (
                <div className="rounded-xl border border-line bg-white p-4 sm:p-5">
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
                            <BackNavLink href={hiringContext.applicationHref} label="Back to application" />
                        ) : null}
                        <BackNavLink href={hiringContext.back} label="Back to applicants" />
                    </div>
                </div>
            ) : null}

            {!ownerDashboardPreview ? (
                <Breadcrumbs
                    items={[
                        { label: 'Home', href: '/' },
                        { label: 'Profiles' },
                        { label: profile.name },
                    ]}
                />
            ) : null}

            {isOwner && !shareEnabled ? (
                <p className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    Public sharing is off. Only you see this preview; enable sharing under Account → Profile → Privacy
                    when you are ready to go live.
                </p>
            ) : null}

            <header className="rounded-xl border border-line bg-white p-5 sm:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-5">
                    <Avatar name={profile.name} src={profile.photo_url ?? undefined} size="xl" shape="circle" />
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h1 className="text-xl font-semibold text-ink sm:text-2xl">{profile.name}</h1>
                                {profile.username ? (
                                    <p className="mt-1 text-sm text-muted">^{profile.username}</p>
                                ) : null}
                                {headerSubtitle ? (
                                    <p className="mt-1 text-sm font-medium text-ink">{headerSubtitle}</p>
                                ) : null}
                                <dl className="mt-3 space-y-1 text-sm">
                                    {profile.location ? (
                                        <div className="flex flex-wrap gap-x-2">
                                            <dt className="text-muted">Location</dt>
                                            <dd className="text-ink">{profile.location}</dd>
                                        </div>
                                    ) : null}
                                    {profile.experience ? (
                                        <div className="flex flex-wrap gap-x-2">
                                            <dt className="text-muted">Experience</dt>
                                            <dd className="text-ink">{profile.experience}</dd>
                                        </div>
                                    ) : null}
                                </dl>
                            </div>
                            <div className="flex shrink-0 flex-wrap items-center gap-2">
                                {shareEnabled && profile.share_url ? (
                                    <PublicShareMenu
                                        label="Share profile"
                                        context={{
                                            type: 'profile',
                                            title: profile.name,
                                            url: profile.share_url,
                                            subtitle: headerSubtitle,
                                        }}
                                    />
                                ) : null}
                                {isOwner ? (
                                    <Link
                                        href="/account/profile"
                                        className="inline-flex min-h-10 items-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-95"
                                    >
                                        Edit profile
                                    </Link>
                                ) : null}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {showSummary ? (
                <SectionCard title="Professional summary">
                    {summaryHeadline ? <p className="text-sm font-medium text-ink">{summaryHeadline}</p> : null}
                    {summaryBio ? (
                        <p
                            className={`whitespace-pre-line text-sm leading-6 text-muted ${summaryHeadline ? 'mt-3' : ''}`}
                        >
                            {summaryBio}
                        </p>
                    ) : null}
                </SectionCard>
            ) : null}

            {employmentHistory.length > 0 ? (
                <SectionCard title="Employment history">
                    <EmploymentTimeline histories={employmentHistory} compact />
                </SectionCard>
            ) : null}

            {(profile.educations ?? []).length > 0 || profile.qualification ? (
                <SectionCard title="Education and qualifications">
                    {(profile.educations ?? []).length > 0 ? (
                        <ul className="space-y-3">
                            {(profile.educations ?? []).map((item, index) => {
                                const dates = formatEducationDates(item.started_on, item.ended_on);
                                return (
                                    <li
                                        key={`${item.institution}-${index}`}
                                        className="border-b border-line pb-3 last:border-0 last:pb-0"
                                    >
                                        <p className="text-sm font-semibold text-ink">{item.institution}</p>
                                        <p className="mt-1 text-sm text-muted">
                                            {[item.qualification, item.field].filter(Boolean).join(' · ') ||
                                                'Details not specified'}
                                        </p>
                                        {dates ? <p className="mt-1 text-sm text-muted">{dates}</p> : null}
                                    </li>
                                );
                            })}
                        </ul>
                    ) : profile.qualification ? (
                        <p className="text-sm text-ink">{profile.qualification}</p>
                    ) : null}
                </SectionCard>
            ) : null}

            {skills.length > 0 ? (
                <SectionCard title="Skills">
                    <ul className="flex flex-wrap gap-2">
                        {skills.map((skill) => (
                            <li key={skill} className="rounded-full bg-surface px-3 py-1 text-sm text-ink">
                                {skill}
                            </li>
                        ))}
                    </ul>
                </SectionCard>
            ) : null}

            {(profile.projects ?? []).length > 0 ? (
                <SectionCard title="Projects">
                    <ul className="space-y-3">
                        {(profile.projects ?? []).map((item, index) => {
                            const dates = formatEducationDates(item.started_on, item.ended_on);
                            return (
                                <li key={`${item.name}-${index}`} className="rounded-lg bg-surface p-4">
                                    <p className="text-sm font-semibold text-ink">{item.name}</p>
                                    <p className="mt-1 text-sm text-muted">
                                        {[item.role, item.company].filter(Boolean).join(' · ')}
                                    </p>
                                    {dates ? <p className="mt-1 text-sm text-muted">{dates}</p> : null}
                                    {item.description ? (
                                        <p className="mt-2 whitespace-pre-line text-sm text-ink">{item.description}</p>
                                    ) : null}
                                </li>
                            );
                        })}
                    </ul>
                </SectionCard>
            ) : null}

            {(profile.certifications ?? []).length > 0 ? (
                <SectionCard title="Certifications">
                    <ul className="space-y-2">
                        {(profile.certifications ?? []).map((item, index) => (
                            <li key={`${item.name}-${index}`} className="text-sm text-ink">
                                <span className="font-medium">{item.name}</span>
                                {item.issuer ? <span className="text-muted"> · {item.issuer}</span> : null}
                                {item.issued_on ? <span className="text-muted"> · {item.issued_on}</span> : null}
                                {item.expires_on ? (
                                    <span className="text-muted"> – expires {item.expires_on}</span>
                                ) : null}
                            </li>
                        ))}
                    </ul>
                </SectionCard>
            ) : null}

            {(profile.languages ?? []).length > 0 ? (
                <SectionCard title="Languages">
                    <ul className="space-y-2 text-sm text-ink">
                        {(profile.languages ?? []).map((item, index) => (
                            <li key={`${item.name}-${index}`}>
                                {item.name}
                                {item.proficiency ? <span className="text-muted"> · {item.proficiency}</span> : null}
                            </li>
                        ))}
                    </ul>
                </SectionCard>
            ) : null}
        </div>
    );

    if (ownerDashboardPreview) {
        return (
            <AccountLayout
                title="Profile preview"
                description="How your profile appears when sharing is enabled. Only fields allowed by your privacy settings are shown to others."
                breadcrumbs={[
                    { label: 'Personal Dashboard', href: '/account' },
                    { label: 'Profile', href: '/account/profile' },
                    { label: 'Preview' },
                ]}
            >
                {previewBody}
            </AccountLayout>
        );
    }

    return (
        <PublicLayout title={profile.name}>
            <section className="py-8 sm:py-10">
                <PageContainer className="max-w-3xl space-y-4">{previewBody}</PageContainer>
            </section>
        </PublicLayout>
    );
}
