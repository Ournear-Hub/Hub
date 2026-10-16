import { Link } from '@inertiajs/react';
import Button from '@/Components/atoms/Button';
import PageContainer from '@/Components/Public/PageContainer';
import OurNearIcon from '@/icons/OurNearIcon';
import type { HirePresentation } from '@/types/hireLanding';
import { scrollToHiringSection } from '@/utils/employerHiringEntry';

type WorkspaceHrefs = {
    createJob: string;
    applications: string;
    pipeline: string;
    companyProfile: string;
};

const CHART_BLUE = '#3977D5';

function SectionIntro({ title, subtitle }: { title: string; subtitle: string }) {
    return (
        <div className="min-w-0">
            <h2 className="text-2xl font-semibold tracking-tight text-ink sm:text-[1.75rem]">{title}</h2>
            <p className="mt-2 max-w-3xl text-sm leading-relaxed text-muted sm:text-[0.9375rem]">{subtitle}</p>
        </div>
    );
}

function DemoTag() {
    return (
        <span className="inline-flex rounded-full border border-line bg-canvas px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-muted">
            Demonstration
        </span>
    );
}

function IllustrativePreviewTag() {
    return (
        <span className="inline-flex rounded-md border border-line bg-canvas px-2 py-0.5 text-[11px] font-medium text-muted">
            Illustrative preview
        </span>
    );
}

function HeroOfferBadge({ children }: { children: string }) {
    return (
        <span className="inline-flex items-center rounded-full border border-white/25 bg-white/95 px-3 py-1.5 text-xs font-semibold text-brand shadow-sm">
            {children}
        </span>
    );
}

function PipelineArrow({ className = '' }: { className?: string }) {
    return (
        <span className={`inline-flex shrink-0 items-center justify-center text-brand/50 ${className}`} aria-hidden="true">
            <OurNearIcon name="arrow-right" size="md" decorative />
        </span>
    );
}

/** Live employers see authorized counts (zero when empty). Illustrative areas use neutral zero, not em dashes. */
function displayCount(value: number | null | undefined, illustrative: boolean): string {
    if (illustrative) {
        return '0';
    }

    return String(value ?? 0);
}

export function HireHeroSection({
    postJobHref,
    workspaceHrefs,
    presentation,
    heroImageUrl,
}: {
    postJobHref: string;
    workspaceHrefs: WorkspaceHrefs;
    presentation: HirePresentation;
    heroImageUrl?: string | null;
}) {
    const live = presentation.mode === 'live' && presentation.workspace !== null;
    const illustrative = !live;
    const kpis = presentation.workspace;
    const activity = presentation.recent_activity ?? [];

    const metrics = [
        { label: 'Active Jobs', value: kpis?.active_jobs, icon: 'briefcase' as const },
        { label: 'Applications', value: kpis?.applications, icon: 'clipboard' as const },
        { label: 'Shortlisted', value: kpis?.shortlisted, icon: 'user' as const },
        { label: 'Interviews', value: kpis?.interviews, icon: 'message' as const },
        { label: 'Offers', value: kpis?.offers, icon: 'check' as const },
        { label: 'Hired', value: kpis?.hired, icon: 'verified-badge' as const },
    ];

    return (
        <section className="relative w-full overflow-hidden border-b border-brand/30 bg-gradient-to-br from-brand via-[#1d3a3f] to-[#163033] text-white">
            {heroImageUrl ? (
                <img
                    src={heroImageUrl}
                    alt=""
                    className="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-15"
                />
            ) : null}
            <PageContainer width="marketing" className="relative py-8 md:py-10 lg:py-11">
                <div className="grid min-w-0 items-center gap-8 lg:grid-cols-2 lg:gap-10 xl:gap-12">
                    <div className="min-w-0">
                        <p className="text-xs font-semibold uppercase tracking-[0.12em] text-white/80">OURNEAR HIRING</p>
                        <h1 className="mt-3 text-[1.85rem] font-bold leading-tight tracking-tight sm:text-[2.125rem] lg:text-[2.375rem]">
                            Hire the <span className="text-accent">right people</span>.
                        </h1>
                        <p className="mt-3 max-w-xl text-sm leading-relaxed text-white/88 sm:text-base">
                            Find the right people with automated resume screening, an organized hiring process and timely updates.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <HeroOfferBadge>Job posting — FREE for 1 month.</HeroOfferBadge>
                            <HeroOfferBadge>ATS (recruitment management) — FREE for 1 year.</HeroOfferBadge>
                        </div>
                        <div className="mt-6 flex flex-col gap-2 sm:flex-row">
                            <Button surface="public" href={postJobHref} variant="primary" className="w-full sm:w-auto">
                                Post Your First Job
                            </Button>
                            <Button
                                surface="public"
                                type="button"
                                variant="ghost"
                                className="w-full border border-white/40 text-white hover:bg-white/10 sm:w-auto"
                                onClick={() => scrollToHiringSection('how-hiring-works')}
                            >
                                See How It Works
                            </Button>
                        </div>
                        <ul className="mt-6 grid gap-3 sm:grid-cols-2">
                            {[
                                ['check', 'No setup fees during the offer.'],
                                ['clipboard', 'Resume screening where implemented.'],
                                ['briefcase', 'Easy job posting.'],
                                ['user', 'Full hiring cycle.'],
                            ].map(([icon, text]) => (
                                <li key={text} className="flex items-start gap-2 text-sm text-white/90">
                                    <span className="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white/10">
                                        <OurNearIcon name={icon} size="sm" className="text-white" decorative />
                                    </span>
                                    <span>{text}</span>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div className="min-w-0 w-full">
                        <div
                            className="w-full rounded-2xl border border-line/20 bg-white p-3.5 text-ink shadow-md sm:p-4"
                            role="img"
                            aria-label={
                                live
                                    ? 'Employer hiring workspace summary from your account'
                                    : 'Illustrative employer hiring workspace layout'
                            }
                        >
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-base font-semibold text-ink">Employer Hiring Workspace</p>
                                        {illustrative ? <IllustrativePreviewTag /> : null}
                                    </div>
                                    {live ? (
                                        <p className="mt-0.5 text-[11px] text-muted">Current workspace totals</p>
                                    ) : (
                                        <p className="mt-0.5 text-[11px] text-muted">Example layout — not your account data</p>
                                    )}
                                </div>
                            </div>
                            <div className="mt-3 grid grid-cols-3 gap-2.5">
                                {metrics.map((item) => (
                                    <div
                                        key={item.label}
                                        className="flex min-h-[4.25rem] flex-col items-center justify-center rounded-xl border border-line bg-canvas/50 px-2 py-2.5 text-center"
                                    >
                                        <OurNearIcon name={item.icon} size="sm" className="text-brand" decorative />
                                        <p className="mt-1 text-xl font-bold tabular-nums leading-none text-ink">
                                            {displayCount(item.value, illustrative)}
                                        </p>
                                        <p className="mt-1 text-[11px] font-medium leading-snug text-muted">{item.label}</p>
                                    </div>
                                ))}
                            </div>
                            {live ? (
                                activity.length > 0 ? (
                                    <ul className="mt-3 space-y-1.5 border-t border-line pt-3">
                                        {activity.map((row, index) => (
                                            <li key={`${row.label}-${index}`} className="flex items-start gap-2 text-xs">
                                                <OurNearIcon name="bell" size="sm" className="mt-0.5 shrink-0 text-brand" decorative />
                                                <span className="min-w-0">
                                                    <span className="font-medium text-ink">{row.label}</span>
                                                    {row.detail ? <span className="block text-muted">{row.detail}</span> : null}
                                                    {row.at ? <span className="block text-[11px] text-muted">{row.at}</span> : null}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="mt-3 border-t border-line pt-3 text-xs text-muted">No recent activity yet.</p>
                                )
                            ) : null}
                            <div className="mt-3 flex flex-wrap gap-2 border-t border-line pt-3">
                                <Link
                                    href={workspaceHrefs.pipeline}
                                    className="rounded-md bg-brand px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-brand/90"
                                >
                                    Open workspace
                                </Link>
                                <Link
                                    href={workspaceHrefs.applications}
                                    className="rounded-md border border-line px-2.5 py-1.5 text-xs font-semibold text-ink hover:bg-canvas"
                                >
                                    View applications
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </PageContainer>
        </section>
    );
}

const howSteps = [
    {
        n: 1,
        title: 'Post a Job',
        body: 'Create and publish your job vacancy.',
        icon: 'briefcase',
        accent: 'bg-brand/10 text-brand',
    },
    {
        n: 2,
        title: 'Share to Multiple Channels',
        body: 'Reach candidates through supported sharing channels.',
        icon: 'share',
        accent: 'bg-[#3977D5]/10 text-[#3977D5]',
    },
    {
        n: 3,
        title: 'Applications & Resume Screening',
        body: 'Receive applications and review candidate resumes.',
        icon: 'clipboard',
        accent: 'bg-accent/10 text-accent',
    },
    {
        n: 4,
        title: 'Manage the Hiring Cycle',
        body: 'Track candidates through the recruitment stages.',
        icon: 'user',
        accent: 'bg-brand/10 text-brand',
    },
    {
        n: 5,
        title: 'Hire',
        body: 'Select the right candidate and close the hiring process.',
        icon: 'check',
        accent: 'bg-accent/10 text-accent',
    },
] as const;

export function HireHowItWorksSection({ postJobHref }: { postJobHref: string }) {
    return (
        <section id="how-hiring-works" className="scroll-mt-24 border-b border-line bg-white py-9 md:py-11">
            <PageContainer width="marketing" className="min-w-0">
                <SectionIntro
                    title="How Hiring Works"
                    subtitle="Five connected steps from posting a vacancy through screening, pipeline management and joining."
                />
                <ol className="mt-8 grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    {howSteps.map((step, index) => (
                        <li
                            key={step.title}
                            className="relative flex min-h-[13.125rem] flex-col rounded-xl border border-line bg-white p-5 transition hover:border-brand/35 hover:shadow-sm"
                        >
                            {index < howSteps.length - 1 ? (
                                <span
                                    className="pointer-events-none absolute -right-3 top-1/2 z-10 hidden -translate-y-1/2 text-brand/40 xl:inline-flex"
                                    aria-hidden="true"
                                >
                                    <OurNearIcon name="arrow-right" size="sm" decorative />
                                </span>
                            ) : null}
                            <span className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-accent text-sm font-bold text-white">
                                {step.n}
                            </span>
                            <span className={`mt-3 inline-flex h-11 w-11 items-center justify-center rounded-xl ${step.accent}`}>
                                <OurNearIcon name={step.icon} size="lg" decorative />
                            </span>
                            <h3 className="mt-3 text-base font-semibold leading-snug text-ink">{step.title}</h3>
                            <p className="mt-2 flex-1 text-sm leading-relaxed text-muted">{step.body}</p>
                            {step.n === 1 ? (
                                <Button surface="public" href={postJobHref} variant="secondary" size="sm" className="mt-4 w-full">
                                    Post a Job
                                </Button>
                            ) : null}
                        </li>
                    ))}
                </ol>
            </PageContainer>
        </section>
    );
}

export function HireScreeningDemoSection() {
    return (
        <section id="resume-screening" className="scroll-mt-24 bg-canvas py-9 md:py-11">
            <PageContainer width="marketing" className="min-w-0">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <SectionIntro
                        title="Automatic Resume Screening / Bulk Upload"
                        subtitle="Upload multiple resumes and get structured candidate information for faster shortlisting."
                    />
                    <DemoTag />
                </div>
                <p className="mt-2 text-xs text-muted">
                    Marketing demonstration on this page. Live bulk resume import and screening run in existing employer and admin
                    workflows — not through this public upload area.
                </p>
                <div className="mt-8 grid gap-4 lg:grid-cols-[1fr_auto_1fr_auto_1fr] lg:items-stretch">
                    <article className="rounded-xl border border-line bg-white p-5 shadow-sm">
                        <h3 className="text-base font-semibold text-ink">Upload Resumes</h3>
                        <div className="mt-4 flex min-h-[10rem] flex-col items-center justify-center rounded-xl border border-dashed border-line bg-canvas p-5 text-center">
                            <OurNearIcon name="upload" size="lg" className="text-brand" decorative />
                            <p className="mt-3 text-sm text-muted">PDF, DOC and DOCX files</p>
                            <p className="mt-2 text-xs text-muted">Demonstration only — not a live upload on this page.</p>
                        </div>
                    </article>
                    <PipelineArrow className="mx-auto hidden lg:flex" />
                    <article className="rounded-xl border border-line bg-white p-5 shadow-sm">
                        <h3 className="text-base font-semibold text-ink">Extracted Information</h3>
                        <dl className="mt-4 space-y-2 text-sm">
                            {[
                                ['Name', 'Sample value'],
                                ['Email', 'sample@example.com'],
                                ['Phone', '+91 …'],
                                ['Experience', '8 years'],
                                ['Skills', 'Structured list'],
                                ['Qualification', 'Degree'],
                            ].map(([label, sample]) => (
                                <div key={label} className="flex justify-between gap-3 border-b border-line/70 py-1.5">
                                    <dt className="text-muted">{label}</dt>
                                    <dd className="font-medium text-ink">{sample}</dd>
                                </div>
                            ))}
                        </dl>
                    </article>
                    <PipelineArrow className="mx-auto hidden lg:flex" />
                    <article className="rounded-xl border border-line bg-white p-5 shadow-sm">
                        <h3 className="text-base font-semibold text-ink">Screening Result</h3>
                        <ul className="mt-4 space-y-2 text-sm">
                            {[
                                ['Candidate A', 'Matched', 'Requirements met for review'],
                                ['Candidate B', 'Review required', 'Recruiter decision'],
                                ['Candidate C', 'Not matched', 'Closed after screening'],
                            ].map(([name, result, note]) => (
                                <li key={name} className="rounded-lg border border-line px-3 py-2">
                                    <p className="font-semibold text-ink">{name}</p>
                                    <p className="text-muted">
                                        {result} · {note}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </article>
                </div>
            </PageContainer>
        </section>
    );
}

const atsStageStyle: Record<string, string> = {
    applied: 'bg-[#3977D5]/15 text-[#3977D5]',
    screening: 'bg-brand/15 text-brand',
    shortlisted: 'bg-accent/15 text-accent',
    interview: 'bg-accent/20 text-accent',
    offer: 'bg-brand/15 text-brand',
    hired: 'bg-emerald-600/15 text-emerald-700',
};

const demoAtsStages = [
    { key: 'applied', label: 'Applied', count: null },
    { key: 'screening', label: 'Screening', count: null },
    { key: 'shortlisted', label: 'Shortlisted', count: null },
    { key: 'interview', label: 'Interview', count: null },
    { key: 'offer', label: 'Offer', count: null },
    { key: 'hired', label: 'Hired', count: null },
];

export function HireAtsSection({ presentation }: { presentation: HirePresentation }) {
    const live = presentation.mode === 'live' && presentation.ats_stages !== null;
    const illustrative = !live;
    const stages = live ? presentation.ats_stages! : demoAtsStages;

    return (
        <section id="ats-pipeline" className="scroll-mt-24 border-y border-line bg-white py-9 md:py-11">
            <PageContainer width="marketing" className="min-w-0">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <SectionIntro title="ATS Hiring Cycle" subtitle="Track every candidate from application to joining." />
                    {!live ? <DemoTag /> : null}
                </div>
                <div className="mt-8 flex gap-3 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:thin]">
                    {stages.map((stage, index) => (
                        <div key={stage.key} className="flex shrink-0 items-center gap-2">
                            <article className="flex w-[9.5rem] flex-col rounded-xl border border-line bg-white p-4 shadow-sm sm:w-[10.25rem]">
                                <span
                                    className={`inline-flex h-10 w-10 items-center justify-center rounded-full ${atsStageStyle[stage.key] ?? 'bg-brand/10 text-brand'}`}
                                >
                                    <OurNearIcon name="briefcase" size="md" decorative />
                                </span>
                                <h3 className="mt-3 text-sm font-bold text-ink">{stage.label}</h3>
                                <p className="mt-2 text-2xl font-bold tabular-nums text-brand">
                                    {displayCount(stage.count, illustrative)}
                                </p>
                                <p className="text-xs text-muted">Candidates</p>
                            </article>
                            {index < stages.length - 1 ? <PipelineArrow className="hidden sm:inline-flex" /> : null}
                        </div>
                    ))}
                </div>
                {!live ? (
                    <p className="mt-4 text-sm text-muted">Sign in as an employer to see live ATS counts from your applications.</p>
                ) : null}
            </PageContainer>
        </section>
    );
}

const notificationEvents = [
    {
        title: 'Application received',
        message: 'Your application was received.',
        email: true,
        inApp: true,
        accent: 'bg-[#3977D5]/10 text-[#3977D5]',
    },
    {
        title: 'Shortlisted or status updated',
        message: 'Your application status was updated.',
        email: true,
        inApp: true,
        accent: 'bg-brand/10 text-brand',
    },
    {
        title: 'Interview scheduled',
        message: 'Your interview has been scheduled.',
        email: true,
        inApp: true,
        accent: 'bg-accent/10 text-accent',
    },
    {
        title: 'Offer sent',
        message: 'An offer has been sent.',
        email: true,
        inApp: false,
        accent: 'bg-brand/10 text-brand',
    },
    {
        title: 'Further status change',
        message: 'Your application status changed.',
        email: false,
        inApp: true,
        accent: 'bg-[#3977D5]/10 text-[#3977D5]',
    },
    {
        title: 'Joined / hired',
        message: 'The hiring process has been completed.',
        email: true,
        inApp: true,
        accent: 'bg-emerald-600/10 text-emerald-700',
    },
];

export function HireNotificationsSection({ communicationSettingsHref }: { communicationSettingsHref: string }) {
    return (
        <section id="hiring-notifications" className="scroll-mt-24 bg-canvas py-9 md:py-11">
            <PageContainer width="marketing" className="min-w-0">
                <SectionIntro
                    title="Automatic Email & In-App Updates"
                    subtitle="Keep candidates and employers informed at every stage."
                />
                <div className="mt-8 flex gap-3 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:thin]">
                    {notificationEvents.map((ev, index) => (
                        <div key={ev.title} className="flex shrink-0 items-stretch gap-2">
                            <article className="flex w-[13.5rem] flex-col rounded-xl border border-line bg-white p-4 shadow-sm sm:w-[14.5rem]">
                                <span className={`inline-flex h-10 w-10 items-center justify-center rounded-full ${ev.accent}`}>
                                    <OurNearIcon name="bell" size="md" decorative />
                                </span>
                                <h3 className="mt-3 text-sm font-bold text-ink">{ev.title}</h3>
                                <p className="mt-1 flex-1 text-xs leading-relaxed text-muted">{ev.message}</p>
                                <p className="mt-3 text-[11px] font-medium text-muted">
                                    {[ev.email ? 'Email' : null, ev.inApp ? 'In-app' : null].filter(Boolean).join(' · ') ||
                                        'Channel varies by preferences'}
                                </p>
                            </article>
                            {index < notificationEvents.length - 1 ? (
                                <PipelineArrow className="hidden self-center md:inline-flex" />
                            ) : null}
                        </div>
                    ))}
                </div>
                <p className="mt-4 text-sm text-muted">
                    Delivery follows existing templates and account preferences.{' '}
                    <Link href={communicationSettingsHref} className="font-semibold text-brand hover:underline">
                        Communication preferences
                    </Link>
                </p>
            </PageContainer>
        </section>
    );
}

function HireBarChart({
    series,
    empty,
    emptyMessage,
}: {
    series: { label: string; value: number }[];
    empty: boolean;
    emptyMessage: string;
}) {
    if (empty || series.every((s) => s.value === 0)) {
        return (
            <div className="flex min-h-[9rem] items-center justify-center rounded-xl border border-dashed border-line bg-canvas px-4 py-6 text-center text-sm text-muted">
                {emptyMessage}
            </div>
        );
    }

    const max = Math.max(...series.map((s) => s.value), 1);

    return (
        <div className="rounded-xl border border-line bg-white p-4">
            <div className="flex h-48 items-end gap-3 border-b border-line pb-2">
                {series.map((item) => (
                    <div key={item.label} className="flex min-w-0 flex-1 flex-col items-center gap-2">
                        <span className="text-xs font-semibold tabular-nums text-ink">{item.value}</span>
                        <div
                            className="w-full max-w-[3rem] rounded-t-md"
                            style={{
                                height: `${Math.max(12, (item.value / max) * 100)}%`,
                                backgroundColor: CHART_BLUE,
                            }}
                            title={`${item.label}: ${item.value}`}
                        />
                        <span className="text-center text-[11px] font-medium leading-tight text-muted">{item.label}</span>
                    </div>
                ))}
            </div>
            <p className="mt-3 text-xs text-muted">Counts reflect your current pipeline — not trends over time.</p>
        </div>
    );
}

export function HireCompanyAndReportsSection({
    presentation,
    previewImageUrl,
    companyProfileHref,
}: {
    presentation: HirePresentation;
    previewImageUrl?: string | null;
    companyProfileHref: string;
}) {
    const live = presentation.mode === 'live';
    const illustrative = !live;
    const company = presentation.company;
    const reports = presentation.reports;
    const chart = presentation.chart;

    const reportCards = [
        { label: 'Total Applications', value: reports?.total_applications, icon: 'clipboard' },
        { label: 'Shortlisted', value: reports?.shortlisted, icon: 'user' },
        { label: 'Interviews', value: reports?.interviews, icon: 'message' },
        { label: 'Offers', value: reports?.offers, icon: 'check' },
        { label: 'Hired', value: reports?.hired, icon: 'verified-badge' },
    ];

    const locationLine =
        live && company ? [company.industry, company.location].filter(Boolean).join(' · ') : '';
    const chartEmptyMessage = live
        ? 'No applications yet — stage counts will appear as candidates move through your pipeline.'
        : 'Illustrative preview — sign in as an employer to see your pipeline snapshot.';

    return (
        <section id="hiring-reports" className="scroll-mt-24 border-t border-line bg-white py-9 md:py-11">
            <PageContainer width="marketing" className="min-w-0">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <SectionIntro
                        title="Reports & Hiring Insights"
                        subtitle={
                            live
                                ? 'Authorized counts from your hiring workspace and a pipeline snapshot.'
                                : 'Illustrative preview of hiring metrics — not live account data.'
                        }
                    />
                    {illustrative ? <IllustrativePreviewTag /> : null}
                </div>

                <div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    {reportCards.map((card) => (
                        <div
                            key={card.label}
                            className="flex min-h-[5.5rem] flex-col justify-center rounded-xl border border-line bg-canvas px-3 py-3 sm:px-4"
                        >
                            <div className="flex items-center gap-2">
                                <OurNearIcon name={card.icon} size="sm" className="shrink-0 text-brand" decorative />
                                <span className="text-xs font-medium leading-snug text-muted">{card.label}</span>
                            </div>
                            <p className="mt-2 text-2xl font-bold tabular-nums text-ink">
                                {displayCount(card.value, illustrative)}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="mt-8 grid gap-8 lg:grid-cols-2 lg:items-start">
                    <div className="min-w-0">
                        <h3 className="text-lg font-semibold text-ink">Employer Company Page</h3>
                        <p className="mt-1 text-sm text-muted">Showcase your company and attract the right talent.</p>
                        {!live ? (
                            <p className="mt-2 text-xs text-muted">Layout preview — not a real company listing.</p>
                        ) : null}
                        <article className="mt-4 overflow-hidden rounded-xl border border-line bg-white shadow-sm">
                            <div className="relative aspect-[3/1] bg-gradient-to-r from-brand/20 to-brand/5">
                                {previewImageUrl ? (
                                    <img src={previewImageUrl} alt="" className="h-full w-full object-cover" />
                                ) : live && company?.about ? null : (
                                    <div className="flex h-full items-center justify-center px-4 text-center text-xs text-muted">
                                        {live ? 'Add a cover image in your company profile.' : 'Cover image area'}
                                    </div>
                                )}
                            </div>
                            <div className="p-4 sm:p-5">
                                <div className="flex items-start gap-3">
                                    {live && company?.logo_url ? (
                                        <img src={company.logo_url} alt="" className="h-12 w-12 rounded-lg border border-line object-cover" />
                                    ) : (
                                        <span className="inline-flex h-12 w-12 items-center justify-center rounded-lg bg-brand/10">
                                            <OurNearIcon name="building" size="lg" className="text-brand" decorative />
                                        </span>
                                    )}
                                    <div className="min-w-0">
                                        <p className="text-base font-semibold text-ink">
                                            {live && company ? company.company_name : 'Company profile preview'}
                                        </p>
                                        <p className="text-sm text-muted">
                                            {live && company
                                                ? locationLine || 'Add industry and location in your company profile.'
                                                : 'Industry and location appear on your public page.'}
                                        </p>
                                    </div>
                                </div>
                                <p className="mt-3 text-sm leading-relaxed text-muted">
                                    {live && company?.about
                                        ? company.about
                                        : live
                                          ? 'Add a company summary in Manage Company Profile to appear on your public page.'
                                          : 'Your company summary and facts help candidates learn about your organization.'}
                                </p>
                                <dl className="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                                    {live && company ? (
                                        <div>
                                            <dt className="text-muted">Open jobs</dt>
                                            <dd className="font-semibold tabular-nums text-ink">{company.open_jobs}</dd>
                                        </div>
                                    ) : null}
                                    {live && company?.company_size ? (
                                        <div>
                                            <dt className="text-muted">Workforce</dt>
                                            <dd className="font-semibold text-ink">{company.company_size}</dd>
                                        </div>
                                    ) : null}
                                    {live && company?.industry ? (
                                        <div>
                                            <dt className="text-muted">Industry</dt>
                                            <dd className="font-semibold text-ink">{company.industry}</dd>
                                        </div>
                                    ) : null}
                                </dl>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {live && company?.public_url ? (
                                        <Button surface="public" href={company.public_url} variant="secondary" size="sm">
                                            View Company Page
                                        </Button>
                                    ) : !live ? (
                                        <Button surface="public" href="/companies" variant="outline" size="sm">
                                            Browse companies
                                        </Button>
                                    ) : null}
                                    {live ? (
                                        <Button surface="public" href={companyProfileHref} variant="outline" size="sm">
                                            Manage Company Profile
                                        </Button>
                                    ) : null}
                                </div>
                            </div>
                        </article>
                    </div>

                    <div className="min-w-0">
                        <h4 className="text-base font-semibold text-ink">Hiring Pipeline Snapshot</h4>
                        <p className="mt-1 text-sm text-muted">
                            {live
                                ? 'Stage counts from your current applications — not historical trends.'
                                : 'Example chart area — pipeline counts appear when you have applications.'}
                        </p>
                        <div className="mt-3">
                            <HireBarChart
                                series={live && chart ? chart.series : []}
                                empty={illustrative || !chart || chart.series.every((s) => s.value === 0)}
                                emptyMessage={chartEmptyMessage}
                            />
                        </div>
                    </div>
                </div>
            </PageContainer>
        </section>
    );
}
