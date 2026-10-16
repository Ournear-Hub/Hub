import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import PageContainer from '@/Components/Public/PageContainer';
import {
    RecruitmentDashboardIllustration,
    RecruitmentShortlistHeroIllustration,
} from '@/Components/Public/PublicLandingIllustrations';
import OurNearIcon from '@/icons/OurNearIcon';
import {
    scrollToTalentPoolProcess,
    TALENT_POOL_PROCESS_SECTION_ID,
} from '@/utils/employerTalentPoolEntry';

function TrustPill({ icon, label }: { icon: string; label: string }) {
    return (
        <span className="inline-flex items-center gap-2 rounded-full border border-line bg-white px-3 py-1.5 text-xs font-semibold text-ink">
            <OurNearIcon name={icon} size="sm" decorative className="text-accent" />
            {label}
        </span>
    );
}

function CheckBenefit({ children }: { children: string }) {
    return (
        <li className="flex items-start gap-2 text-sm text-muted">
            <OurNearIcon name="check" size="sm" className="mt-0.5 shrink-0 text-accent" decorative />
            <span>{children}</span>
        </li>
    );
}

function IconCard({
    icon,
    title,
    body,
}: {
    icon: string;
    title: string;
    body: string;
}) {
    return (
        <Card as="article" padding="standard" className="h-full text-center sm:text-left">
            <span className="inline-flex rounded-lg bg-brand/10 p-2.5">
                <OurNearIcon name={icon} size="md" decorative className="text-brand" />
            </span>
            <h3 className="mt-3 text-base font-semibold text-ink">{title}</h3>
            <p className="mt-2 text-sm leading-relaxed text-muted">{body}</p>
        </Card>
    );
}

const HERO_BENEFITS = [
    { icon: 'search', label: 'Sourcing & screening by our team' },
    { icon: 'user', label: 'Shortlisted candidate profiles' },
    { icon: 'clipboard', label: 'Easy-to-manage recruitment dashboard' },
    { icon: 'verified-badge', label: 'Affordable recruitment support' },
] as const;

export function TalentPoolHeroSection({
    requirementHref,
    heroImageUrl,
    heroImageAlt,
    heroBody,
}: {
    requirementHref: string;
    heroImageUrl?: string | null;
    heroImageAlt?: string | null;
    heroBody?: string | null;
}) {
    const supporting =
        heroBody?.trim() ||
        'Hiring shouldn\'t consume your time, energy and resources. OurNear Hire sources, screens and shortlists suitable candidates for your requirements, so your team can focus on running the business.';

    return (
        <section className="border-b border-line bg-gradient-to-b from-brand/5 to-white py-10 md:py-14">
            <PageContainer className="grid min-w-0 items-center gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-12">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-brand">OURNEAR HIRE · TALENT POOL</p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight text-ink sm:text-3xl lg:text-[2rem] lg:leading-tight">
                        Your Recruitment Partner.
                        <span className="mt-1 block text-accent">We Find Your People.</span>
                    </h1>
                    <p className="mt-3 max-w-xl text-sm leading-relaxed text-muted sm:text-base">{supporting}</p>
                    <div className="mt-6 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <Button
                            surface="public"
                            href={requirementHref}
                            variant="primary"
                            className="w-full sm:w-auto"
                            aria-label="Submit hiring requirement via contact form"
                        >
                            Submit Hiring Requirement
                        </Button>
                        <Button
                            surface="public"
                            type="button"
                            variant="outline"
                            className="w-full sm:w-auto"
                            onClick={() => scrollToTalentPoolProcess()}
                        >
                            How It Works
                        </Button>
                    </div>
                    <ul className="mt-8 flex flex-wrap gap-2">
                        {HERO_BENEFITS.map((item) => (
                            <li key={item.label}>
                                <TrustPill icon={item.icon} label={item.label} />
                            </li>
                        ))}
                    </ul>
                </div>
                <div className="min-w-0">
                    {heroImageUrl ? (
                        <img
                            src={heroImageUrl}
                            alt={
                                heroImageAlt?.trim() ||
                                'Employer reviewing shortlisted candidate profiles with OurNear recruitment support'
                            }
                            className="aspect-[4/3] w-full rounded-xl border border-line object-cover shadow-sm"
                            width={640}
                            height={480}
                            loading="eager"
                            fetchPriority="high"
                        />
                    ) : (
                        <RecruitmentShortlistHeroIllustration />
                    )}
                </div>
            </PageContainer>
        </section>
    );
}

const DIFFERENCE_CARDS = [
    {
        icon: 'search',
        title: 'We Source',
        body: 'Find candidates relevant to your job requirements from our network and permitted sourcing channels.',
    },
    {
        icon: 'file',
        title: 'We Screen',
        body: 'Evaluate resumes, qualifications, experience and suitability against your requirements.',
    },
    {
        icon: 'clipboard',
        title: 'We Shortlist',
        body: 'Receive the best-matched candidate profiles with relevant details for your review.',
    },
    {
        icon: 'check',
        title: 'You Select',
        body: 'Review profiles, interview your preferred candidates and make the final hiring decision.',
    },
] as const;

export function TalentPoolDifferenceSection() {
    return (
        <section className="border-b border-line bg-white py-10 md:py-14">
            <PageContainer className="min-w-0">
                <p className="text-xs font-semibold uppercase tracking-wide text-brand">THE DIFFERENCE</p>
                <h2 className="mt-2 text-xl font-semibold text-ink sm:text-2xl">More Than a Job Portal</h2>
                <p className="mt-2 max-w-3xl text-sm text-muted">
                    You don&apos;t have to manage hundreds of applications or spend hours screening resumes. Our
                    recruitment team does the hard work for you.
                </p>
                <div className="mt-8 hidden items-stretch gap-2 lg:flex">
                    {DIFFERENCE_CARDS.map((card, index) => (
                        <div key={card.title} className="flex min-w-0 flex-1 items-stretch gap-2">
                            <div className="flex-1">
                                <IconCard icon={card.icon} title={card.title} body={card.body} />
                            </div>
                            {index < DIFFERENCE_CARDS.length - 1 ? (
                                <OurNearIcon
                                    name="arrow-right"
                                    size="md"
                                    decorative
                                    className="hidden shrink-0 self-center text-brand/30 xl:block"
                                />
                            ) : null}
                        </div>
                    ))}
                </div>
                <ul className="mt-8 grid gap-4 sm:grid-cols-2 lg:hidden">
                    {DIFFERENCE_CARDS.map((card) => (
                        <li key={card.title}>
                            <IconCard icon={card.icon} title={card.title} body={card.body} />
                        </li>
                    ))}
                </ul>
            </PageContainer>
        </section>
    );
}

const PROCESS_STEPS = [
    {
        number: '01',
        title: 'Tell Us Your Requirement',
        body: 'Submit job details, vacancy numbers, skills, experience, location and salary range.',
        icon: 'clipboard',
    },
    {
        number: '02',
        title: 'We Find & Screen Candidates',
        body: 'Our team sources candidates and evaluates their fit against your requirements.',
        icon: 'search',
    },
    {
        number: '03',
        title: 'We Send Shortlisted Profiles',
        body: 'Review relevant resumes and candidate profiles through your employer dashboard.',
        icon: 'user',
    },
    {
        number: '04',
        title: 'Coordinate Interviews',
        body: 'Track interview schedules, feedback and recruitment progress.',
        icon: 'message',
    },
    {
        number: '05',
        title: 'Hire the Right People',
        body: 'Choose your preferred candidates and complete your hiring process.',
        icon: 'check',
    },
] as const;

export function TalentPoolProcessSection() {
    return (
        <section
            id={TALENT_POOL_PROCESS_SECTION_ID}
            className="scroll-mt-20 border-b border-line bg-canvas py-10 md:py-14"
        >
            <PageContainer className="min-w-0">
                <h2 className="text-xl font-semibold text-ink sm:text-2xl">A Smoother Way to Hire</h2>
                <p className="mt-2 max-w-3xl text-sm text-muted">
                    From requirement to shortlisted talent — managed in one place.
                </p>
                <ol className="mt-10 hidden list-none gap-4 xl:grid xl:grid-cols-5">
                    {PROCESS_STEPS.map((step, index) => (
                        <li key={step.number} className="relative flex min-w-0 flex-col">
                            {index < PROCESS_STEPS.length - 1 ? (
                                <span
                                    className="pointer-events-none absolute left-[calc(50%+1.5rem)] top-6 hidden h-px w-[calc(100%-3rem)] bg-line xl:block"
                                    aria-hidden="true"
                                />
                            ) : null}
                            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-brand text-sm font-semibold text-white">
                                {step.number}
                            </span>
                            <OurNearIcon name={step.icon} size="md" decorative className="mt-3 text-accent" />
                            <h3 className="mt-3 text-sm font-semibold text-ink">{step.title}</h3>
                            <p className="mt-1 text-xs leading-relaxed text-muted">{step.body}</p>
                        </li>
                    ))}
                </ol>
                <ol className="mt-8 space-y-6 border-l-2 border-brand/20 pl-6 xl:hidden">
                    {PROCESS_STEPS.map((step) => (
                        <li key={step.number} className="relative list-none">
                            <span
                                className="absolute -left-[calc(1.5rem+5px)] top-1 flex h-3 w-3 rounded-full bg-accent ring-4 ring-canvas"
                                aria-hidden="true"
                            />
                            <p className="text-xs font-semibold tabular-nums text-accent">{step.number}</p>
                            <div className="mt-1 flex items-start gap-2">
                                <OurNearIcon name={step.icon} size="sm" decorative className="mt-0.5 shrink-0 text-brand" />
                                <div>
                                    <h3 className="text-base font-semibold text-ink">{step.title}</h3>
                                    <p className="mt-1 text-sm text-muted">{step.body}</p>
                                </div>
                            </div>
                        </li>
                    ))}
                </ol>
            </PageContainer>
        </section>
    );
}

export function TalentPoolManualWorkSection({
    sectionImageUrl,
    sectionImageAlt,
    sectionBody,
}: {
    sectionImageUrl?: string | null;
    sectionImageAlt?: string | null;
    sectionBody?: string | null;
}) {
    const supporting =
        sectionBody?.trim() ||
        'Our recruitment team and ATS platform help reduce time-consuming administrative tasks, so you can focus on your business.';

    return (
        <section className="border-b border-line bg-[#f7f4f0] py-10 md:py-14">
            <PageContainer className="grid min-w-0 items-center gap-10 lg:grid-cols-2 lg:gap-12">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-brand">LESS WORK. MORE RESULTS.</p>
                    <h2 className="mt-2 text-xl font-semibold text-ink sm:text-2xl">Reduce Manual Recruitment Work</h2>
                    <p className="mt-3 text-sm leading-relaxed text-muted sm:text-base">{supporting}</p>
                    <ul className="mt-6 space-y-2">
                        <CheckBenefit>Less time spent sorting resumes.</CheckBenefit>
                        <CheckBenefit>Fewer repetitive candidate follow-ups.</CheckBenefit>
                        <CheckBenefit>Recruitment stages tracked in one place.</CheckBenefit>
                        <CheckBenefit>Easier interview coordination.</CheckBenefit>
                        <CheckBenefit>Clearer visibility into selection and joining progress.</CheckBenefit>
                    </ul>
                </div>
                <div className="min-w-0">
                    {sectionImageUrl ? (
                        <img
                            src={sectionImageUrl}
                            alt={sectionImageAlt?.trim() || 'Recruitment workflow summary illustration'}
                            className="w-full rounded-xl border border-line object-cover shadow-sm"
                            loading="lazy"
                        />
                    ) : (
                        <RecruitmentDashboardIllustration />
                    )}
                </div>
            </PageContainer>
        </section>
    );
}

const WHY_CARDS = [
    {
        icon: 'user',
        title: 'Less Mental Pressure',
        body: 'Let recruitment specialists handle the operational workload.',
    },
    {
        icon: 'search',
        title: 'Relevant Talent',
        body: 'Review candidate profiles matched to your specific requirements.',
    },
    {
        icon: 'clipboard',
        title: 'Better Visibility',
        body: 'Track requirements, interviews and candidate progress in one place.',
    },
    {
        icon: 'briefcase',
        title: 'Affordable Hiring Support',
        body: 'Access practical recruitment support and ATS capabilities under a clear service model.',
    },
] as const;

export function TalentPoolWhySection() {
    return (
        <section className="border-b border-line bg-white py-10 md:py-14">
            <PageContainer className="min-w-0">
                <h2 className="text-xl font-semibold text-ink sm:text-2xl">Why Employers Choose OurNear Hire</h2>
                <ul className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {WHY_CARDS.map((card) => (
                        <li key={card.title}>
                            <IconCard icon={card.icon} title={card.title} body={card.body} />
                        </li>
                    ))}
                </ul>
            </PageContainer>
        </section>
    );
}

const DEFAULT_INDUSTRIES = [
    'Construction',
    'Mining',
    'Infrastructure',
    'Manufacturing',
    'Logistics',
    'Oil & Gas',
    'Hospitality',
    'Healthcare',
    'IT & Corporate',
] as const;

export function TalentPoolIndustriesSection({ industries }: { industries: string[] }) {
    const labels = industries.length > 0 ? industries.slice(0, 12) : [...DEFAULT_INDUSTRIES];

    return (
        <section className="border-b border-line bg-canvas py-10 md:py-14">
            <PageContainer className="min-w-0">
                <h2 className="text-xl font-semibold text-ink sm:text-2xl">
                    Talent for the Industries That Keep Business Moving
                </h2>
                <p className="mt-2 max-w-3xl text-sm text-muted">
                    Recruitment support for a range of professional and operational roles.
                </p>
                <ul className="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-3">
                    {labels.map((label) => (
                        <li
                            key={label}
                            className="flex items-center gap-2 rounded-lg border border-line bg-white px-3 py-3 text-sm font-medium text-ink"
                        >
                            <OurNearIcon name="building" size="sm" decorative className="shrink-0 text-brand" />
                            <span className="min-w-0">{label}</span>
                        </li>
                    ))}
                </ul>
            </PageContainer>
        </section>
    );
}

export function TalentPoolFinalCtaSection({
    requirementHref,
    ctaImageUrl,
    ctaBody,
}: {
    requirementHref: string;
    ctaImageUrl?: string | null;
    ctaBody?: string | null;
}) {
    const supporting =
        ctaBody?.trim() ||
        'Share your recruitment requirements and let OurNear Hire source, shortlist and coordinate the right talent — while your team focuses on growing the business.';

    return (
        <section className="bg-brand py-12 text-white md:py-16">
            <PageContainer className="grid min-w-0 items-center gap-10 lg:grid-cols-[1.1fr_0.9fr]">
                <div className="min-w-0">
                    <h2 className="text-2xl font-semibold sm:text-3xl">Your Next Great Hire Starts Here.</h2>
                    <p className="mt-3 max-w-xl text-sm leading-relaxed text-white/85 sm:text-base">{supporting}</p>
                    <Button
                        surface="public"
                        href={requirementHref}
                        variant="primary"
                        className="mt-6 w-full bg-accent hover:bg-accent/90 sm:w-auto"
                        aria-label="Submit your hiring requirement via contact form"
                    >
                        Submit Your Hiring Requirement
                    </Button>
                </div>
                <div className="min-w-0 lg:justify-self-end">
                    {ctaImageUrl ? (
                        <img
                            src={ctaImageUrl}
                            alt=""
                            className="aspect-[4/3] w-full max-w-md rounded-xl border border-white/20 object-cover opacity-95"
                            loading="lazy"
                        />
                    ) : (
                        <div
                            className="aspect-[4/3] w-full max-w-md rounded-xl border border-white/15 bg-white/5 p-6"
                            aria-hidden="true"
                        >
                            <OurNearIcon name="user" size="lg" decorative className="text-accent opacity-80" />
                            <OurNearIcon name="clipboard" size="lg" decorative className="mt-4 text-white/60" />
                        </div>
                    )}
                </div>
            </PageContainer>
        </section>
    );
}
