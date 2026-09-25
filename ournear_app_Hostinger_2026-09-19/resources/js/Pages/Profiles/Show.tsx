import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import type { SiteContent } from '@/types/public';

type Props = {
    content: SiteContent;
    profile: {
        name: string;
        username: string | null;
        photo_url: string | null;
        bio: string | null;
        designation: string | null;
        current_company: string | null;
        industry: string | null;
        functional_area: string | null;
        experience: string | null;
        qualification: string | null;
        skills: string[];
        employment_history: Array<{
            job_title: string | null;
            company_name: string | null;
            industry: string | null;
            is_current: boolean;
        }>;
    };
};

export default function PublicCandidateProfile({ profile }: Props) {
    const initials = profile.name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();

    return (
        <PublicLayout title={profile.name}>
            <section className="py-10">
                <PageContainer className="max-w-4xl">
                    <article className="rounded-xl border border-line bg-white p-6 sm:p-8">
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
                            {profile.photo_url ? (
                                <img src={profile.photo_url} alt={`${profile.name} profile`} className="h-28 w-28 rounded-full object-cover" />
                            ) : (
                                <div className="flex h-28 w-28 items-center justify-center rounded-full bg-surface text-2xl font-semibold text-brand">{initials}</div>
                            )}
                            <div>
                                <h1 className="text-3xl font-semibold text-ink">{profile.name}</h1>
                                {profile.username ? <p className="mt-1 text-sm text-muted">@{profile.username}</p> : null}
                                {profile.designation ? <p className="mt-2 font-medium text-ink">{profile.designation}</p> : null}
                                {profile.current_company ? <p className="mt-1 text-sm text-muted">{profile.current_company}</p> : null}
                            </div>
                        </div>
                        {profile.bio ? <p className="mt-6 whitespace-pre-line text-sm leading-6 text-muted">{profile.bio}</p> : null}
                        <dl className="mt-8 grid gap-4 sm:grid-cols-2">
                            {[
                                ['Industry', profile.industry],
                                ['Functional area', profile.functional_area],
                                ['Experience', profile.experience],
                                ['Qualification', profile.qualification],
                            ].filter(([, value]) => value).map(([label, value]) => (
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
                                    {profile.skills.map((skill) => <span key={skill} className="rounded-full bg-surface px-3 py-1 text-sm text-ink">{skill}</span>)}
                                </div>
                            </section>
                        ) : null}
                        {profile.employment_history.length > 0 ? (
                            <section className="mt-8">
                                <h2 className="text-lg font-semibold text-ink">Professional experience</h2>
                                <div className="mt-3 space-y-3">
                                    {profile.employment_history.map((history, index) => (
                                        <div key={`${history.company_name}-${history.job_title}-${index}`} className="rounded-lg bg-surface p-4">
                                            <p className="font-medium text-ink">{history.job_title || 'Role'}</p>
                                            <p className="mt-1 text-sm text-muted">
                                                {[history.company_name, history.industry, history.is_current ? 'Current' : null].filter(Boolean).join(' · ')}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        ) : null}
                    </article>
                    <p className="mt-4 text-xs text-muted">
                        Private resumes, documents, KYC, and account details are never included on this public profile.
                    </p>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
