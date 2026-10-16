import { Link } from '@inertiajs/react';
import { createContext, useContext, useState, type ReactNode } from 'react';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import EmployerManpowerRequestForm from '@/Components/Public/EmployerManpowerRequestForm';
import PageContainer from '@/Components/Public/PageContainer';
import OurNearIcon from '@/icons/OurNearIcon';
import { STAFFING_REQUEST_SECTION_ID } from '@/utils/accountDashboardEntry';
import {
    staffingHasDisplayableImage,
    type StaffingImageConfig,
} from '@/lib/staffingCmsImages';

type StaffingCtaContextValue = {
    workspaceHref: string;
    registerCompanyHref: string;
    showRegisterCompany: boolean;
};

const StaffingCtaContext = createContext<StaffingCtaContextValue>({
    workspaceHref: '/login?intended=%2Faccount%2Fenquiries',
    registerCompanyHref: '/account/hiring?destination=%2Faccount%2Fenquiries',
    showRegisterCompany: true,
});

export function StaffingCtaProvider({
    workspaceHref,
    registerCompanyHref,
    showRegisterCompany,
    children,
}: StaffingCtaContextValue & { children: ReactNode }) {
    return (
        <StaffingCtaContext.Provider value={{ workspaceHref, registerCompanyHref, showRegisterCompany }}>
            {children}
        </StaffingCtaContext.Provider>
    );
}

function TrustPill({ icon, label }: { icon: string; label: string }) {
    return (
        <span className="inline-flex items-center gap-2 rounded-full border border-line bg-white px-3 py-1.5 text-xs font-semibold text-ink">
            <OurNearIcon name={icon} size="sm" decorative className="text-accent" />
            {label}
        </span>
    );
}

function StaffingWorkspaceButton({ className = '' }: { className?: string }) {
    const { workspaceHref } = useContext(StaffingCtaContext);

    return (
        <Button
            surface="public"
            href={workspaceHref}
            variant="primary"
            trailingIcon="arrow-right"
            className={className}
        >
            Open Staffing Workspace
        </Button>
    );
}

function StaffingWorkspaceInlineLink() {
    const { workspaceHref } = useContext(StaffingCtaContext);

    return (
        <Link href={workspaceHref} className="font-semibold text-brand underline-offset-2 hover:underline">
            Open staffing workspace
        </Link>
    );
}

function RegisterCompanyButton({ className = '' }: { className?: string }) {
    const { registerCompanyHref, showRegisterCompany } = useContext(StaffingCtaContext);
    if (!showRegisterCompany) {
        return null;
    }

    return (
        <Button surface="public" href={registerCompanyHref} variant="outline" className={className}>
            Register Company
        </Button>
    );
}

function StaffingIllustrationFrame({
    config,
    loading = 'lazy',
    variant = 'hero',
}: {
    config: StaffingImageConfig;
    loading?: 'eager' | 'lazy';
    variant?: 'hero' | 'partner';
}) {
    const [loadFailed, setLoadFailed] = useState(false);
    const desktop = config.desktopUrl ?? config.mobileUrl;
    const mobile = config.mobileUrl ?? config.desktopUrl;

    if (!staffingHasDisplayableImage(config) || loadFailed || !desktop) {
        return null;
    }

    const shellClass =
        variant === 'hero'
            ? 'relative flex aspect-[4/3] min-h-[14rem] w-full min-w-0 items-center justify-center overflow-hidden rounded-xl border border-line bg-gradient-to-br from-brand/5 via-white to-accent/5 p-3 shadow-sm max-h-[min(22rem,52vw)] lg:max-h-[22rem]'
            : 'relative flex aspect-[4/3] min-h-[16rem] w-full min-w-0 items-center justify-center overflow-hidden rounded-xl border border-line bg-gradient-to-br from-brand/5 via-white to-accent/5 p-4 shadow-sm lg:min-h-[20rem]';

    const imgClass = 'max-h-full max-w-full object-contain';

    const altText = config.alt || (variant === 'hero' ? 'Workforce services' : 'Workforce partnership');

    if (mobile && mobile !== desktop) {
        return (
            <div className={shellClass}>
                <img
                    src={mobile}
                    alt={altText}
                    className={`${imgClass} md:hidden`}
                    style={{ objectPosition: config.mobileObjectPosition }}
                    loading={loading}
                    fetchPriority={loading === 'eager' ? 'high' : undefined}
                    onError={() => setLoadFailed(true)}
                />
                <img
                    src={desktop}
                    alt={altText}
                    className={`${imgClass} hidden md:block`}
                    style={{ objectPosition: config.objectPosition }}
                    loading={loading}
                    fetchPriority={loading === 'eager' ? 'high' : undefined}
                    onError={() => setLoadFailed(true)}
                />
            </div>
        );
    }

    return (
        <div className={shellClass}>
            <img
                src={desktop}
                alt={altText}
                className={imgClass}
                style={{ objectPosition: config.objectPosition }}
                loading={loading}
                fetchPriority={loading === 'eager' ? 'high' : undefined}
                onError={() => setLoadFailed(true)}
            />
        </div>
    );
}

function PartnerBenefitCard({
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

function ProcessStepIcon({ name }: { name: string }) {
    return (
        <span className="mx-auto inline-flex rounded-lg bg-brand/10 p-2.5 sm:mx-0">
            <OurNearIcon name={name} size="md" decorative className="text-brand" />
        </span>
    );
}

export function StaffingHeroSection({
    heroImage,
}: {
    heroImage: StaffingImageConfig;
}) {
    const supporting =
        'OurNear understands your workforce requirements, sources suitable people, coordinates deployment, and supports your team after they join—so you can stay focused on running your business.';
    const showPhoto = staffingHasDisplayableImage(heroImage);

    return (
        <section className="border-b border-line bg-gradient-to-b from-brand/5 to-white py-10 md:py-14">
            <PageContainer
                className={`grid min-w-0 items-center gap-10 ${showPhoto ? 'lg:grid-cols-[1.05fr_0.95fr] lg:gap-12' : 'max-w-3xl'}`}
            >
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-brand">OURNEAR HIRE · STAFFING</p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight text-ink sm:text-3xl lg:text-[1.75rem] lg:leading-snug xl:text-[2rem]">
                        You Focus on Your Business. We Take Care of Your People.
                    </h1>
                    <p className="mt-3 max-w-xl text-sm leading-relaxed text-muted sm:text-base">{supporting}</p>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <TrustPill icon="verified-badge" label="Right People" />
                        <TrustPill icon="clipboard" label="Reliable Process" />
                        <TrustPill icon="message" label="Ongoing Support" />
                        <TrustPill icon="check" label="Your Satisfaction" />
                    </div>
                    <div className="mt-5 flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center">
                        <StaffingWorkspaceButton className="w-full sm:w-auto" />
                        <RegisterCompanyButton className="w-full sm:w-auto" />
                    </div>
                </div>
                {showPhoto ? (
                    <div className="min-w-0">
                        <StaffingIllustrationFrame config={heroImage} loading="eager" variant="hero" />
                    </div>
                ) : null}
            </PageContainer>
        </section>
    );
}

const PROCESS_STEPS = [
    {
        number: 1,
        title: 'Share Your Requirement',
        body: 'Tell us the roles or skills you need, headcount, location, timing, and any other agreed requirements.',
        icon: 'clipboard',
    },
    {
        number: 2,
        title: 'We Source People',
        body: 'We identify suitable candidates through the sourcing channels available for your requirement.',
        icon: 'search',
    },
    {
        number: 3,
        title: 'We Screen & Verify',
        body: 'Suitability is assessed against agreed criteria and applicable verification requirements.',
        icon: 'verified-badge',
    },
    {
        number: 4,
        title: 'We Coordinate Deployment',
        body: 'Selection, joining, and deployment details are coordinated with you through the process.',
        icon: 'building',
    },
    {
        number: 5,
        title: 'We Support You',
        body: 'Regular updates about deployed employees are provided according to your agreed arrangements.',
        icon: 'message',
    },
    {
        number: 6,
        title: 'Replacement Assistance',
        body: 'Replacement requests are handled promptly, subject to the agreed replacement terms.',
        icon: 'arrow-right',
    },
] as const;

export function StaffingProcessSection() {
    return (
        <section id="staffing-process" className="scroll-mt-20 border-b border-line bg-canvas py-10 md:py-14">
            <PageContainer className="min-w-0">
                <p className="text-xs font-semibold uppercase tracking-wide text-brand">HOW OUR STAFFING WORKS</p>
                <h2 className="mt-2 text-xl font-semibold text-ink sm:text-2xl">
                    A Complete Process. From Your Requirement to Ongoing Support.
                </h2>
                <p className="mt-2 max-w-3xl text-sm text-muted">
                    Workforce requirement, sourcing, deployment, and ongoing support—managed as one coordinated
                    staffing workflow, not a one-time handoff.
                </p>
                <ol className="mt-8 hidden list-none auto-rows-fr gap-4 md:grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    {PROCESS_STEPS.map((step) => (
                        <li
                            key={step.number}
                            className="flex h-full min-w-0 flex-col rounded-xl border border-line bg-white p-4 text-center shadow-sm"
                        >
                            <span className="mx-auto flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-semibold text-white">
                                {step.number}
                            </span>
                            <div className="mt-3 flex justify-center">
                                <ProcessStepIcon name={step.icon} />
                            </div>
                            <h3 className="mt-3 text-sm font-semibold leading-snug text-ink">{step.title}</h3>
                            <p className="mt-2 flex-1 text-xs leading-relaxed text-muted">{step.body}</p>
                        </li>
                    ))}
                </ol>
                <ol className="mt-8 space-y-4 md:hidden">
                    {PROCESS_STEPS.map((step) => (
                        <li
                            key={step.number}
                            className="list-none rounded-xl border border-line bg-white p-4 shadow-sm"
                        >
                            <p className="text-xs font-semibold uppercase tracking-wide text-brand">Step {step.number}</p>
                            <div className="mt-2 flex items-start gap-3">
                                <ProcessStepIcon name={step.icon} />
                                <div className="min-w-0 pt-0.5">
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

const PARTNER_CARDS = [
    {
        title: 'Reliable People',
        body: 'We focus on suitability and quality—not merely filling numbers.',
        icon: 'verified-badge',
    },
    {
        title: 'Transparent Process',
        body: 'Clear communication at every stage so you know what to expect.',
        icon: 'message',
    },
    {
        title: 'Regular Updates',
        body: 'Updates about your deployed workforce according to agreed arrangements.',
        icon: 'clipboard',
    },
    {
        title: 'Replacement Support',
        body: 'Prompt coordination of replacement requests, subject to agreed terms.',
        icon: 'arrow-right',
    },
] as const;

export function StaffingWorkforcePartnerSection({ partnerImage }: { partnerImage: StaffingImageConfig }) {
    const showIllustration = staffingHasDisplayableImage(partnerImage);

    return (
        <section className="border-b border-line bg-white py-10 md:py-14">
            <PageContainer
                className={`grid min-w-0 items-center gap-10 ${showIllustration ? 'lg:grid-cols-2 lg:gap-12' : ''}`}
            >
                {showIllustration ? (
                    <div className="min-w-0">
                        <StaffingIllustrationFrame config={partnerImage} variant="partner" />
                    </div>
                ) : null}
                <div className={`min-w-0 ${showIllustration ? '' : 'max-w-3xl'}`}>
                    <p className="text-xs font-semibold uppercase tracking-wide text-brand">WORKFORCE PARTNER</p>
                    <h2 className="mt-2 text-xl font-semibold text-ink sm:text-2xl">
                        More Than Manpower. A True Workforce Partner.
                    </h2>
                    <p className="mt-3 max-w-2xl text-sm leading-relaxed text-muted sm:text-base">
                        OurNear aims to understand your business needs and support continuity throughout the workforce
                        engagement—not only at deployment.
                    </p>
                    <ul className="mt-8 grid gap-4 sm:grid-cols-2">
                        {PARTNER_CARDS.map((card) => (
                            <li key={card.title}>
                                <PartnerBenefitCard icon={card.icon} title={card.title} body={card.body} />
                            </li>
                        ))}
                    </ul>
                    <div className="mt-8">
                        <StaffingWorkspaceButton className="w-full sm:w-auto" />
                    </div>
                </div>
            </PageContainer>
        </section>
    );
}

const RESPONSIBILITY_ITEMS = [
    'Dedicated coordination.',
    'Regular employee updates.',
    'Timely issue handling.',
    'Replacement assistance.',
    'Ongoing communication.',
] as const;

export function StaffingResponsibilitySection() {
    return (
        <section className="bg-brand py-10 text-white md:py-14">
            <PageContainer className="min-w-0">
                <div className="grid gap-8 lg:grid-cols-2 lg:items-center">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-accent">
                            YOUR WORKFORCE. OUR RESPONSIBILITY.
                        </p>
                        <h2 className="mt-2 text-xl font-semibold sm:text-2xl">We Work for You. Your Satisfaction Matters.</h2>
                        <p className="mt-3 max-w-xl text-sm leading-relaxed text-white/85">
                            We commit to understanding your requirements, offering suitable workforce options,
                            communicating clearly, addressing issues, and working toward your satisfaction throughout
                            the engagement.
                        </p>
                    </div>
                    <div>
                        <ul className="space-y-2">
                            {RESPONSIBILITY_ITEMS.map((item) => (
                                <li key={item} className="flex items-start gap-2 text-sm text-white/90">
                                    <OurNearIcon name="check" size="sm" decorative className="mt-0.5 shrink-0 text-accent" />
                                    <span>{item}</span>
                                </li>
                            ))}
                        </ul>
                        <div className="mt-6">
                            <StaffingWorkspaceButton />
                        </div>
                    </div>
                </div>
            </PageContainer>
        </section>
    );
}

const EXPECT_COLUMNS = [
    {
        title: 'Understanding',
        body: 'Listen to your requirement and agree on the approach.',
        icon: 'info',
    },
    {
        title: 'Right People',
        body: 'Source and assess candidates against agreed needs.',
        icon: 'verified-badge',
    },
    {
        title: 'Smooth Deployment',
        body: 'Coordinate the deployment process with you.',
        icon: 'briefcase',
    },
    {
        title: 'Ongoing Support',
        body: 'Stay connected after joining and provide agreed updates.',
        icon: 'message',
    },
] as const;

export function StaffingRequirementSection({
    industries,
    workforceTypes,
    successMessage,
}: {
    industries: string[];
    workforceTypes: string[];
    successMessage?: string | null;
}) {
    return (
        <section id={STAFFING_REQUEST_SECTION_ID} className="border-b border-line bg-white py-10 md:py-14 scroll-mt-24">
            <PageContainer className="min-w-0">
                <div className="max-w-3xl">
                    <h2 className="text-xl font-semibold text-ink sm:text-2xl">Submit a staffing requirement</h2>
                    <p className="mt-2 text-sm text-muted">
                        Share your workforce need directly — no account, email verification, or administrator approval is
                        required before submission. OurNear will follow up after your enquiry is received.
                    </p>
                </div>
                <Card className="mt-8 max-w-4xl p-6">
                    <EmployerManpowerRequestForm
                        industries={industries}
                        workforceTypes={workforceTypes}
                        successMessage={successMessage}
                    />
                </Card>
                <p className="mt-4 max-w-3xl text-xs text-muted">
                    Already have a company workspace?{' '}
                    <StaffingWorkspaceInlineLink />
                </p>
            </PageContainer>
        </section>
    );
}

export function StaffingExpectSection() {
    return (
        <section className="border-b border-line bg-canvas py-10 md:py-14">
            <PageContainer className="min-w-0">
                <h2 className="text-xl font-semibold text-ink sm:text-2xl">What You Can Expect</h2>
                <p className="mt-2 max-w-2xl text-sm text-muted">
                    A calm, consistent partnership from first conversation through deployment and beyond.
                </p>
                <ul className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {EXPECT_COLUMNS.map((column) => (
                        <li key={column.title} className="min-w-0 text-center sm:text-left">
                            <OurNearIcon name={column.icon} size="md" decorative className="mx-auto text-accent sm:mx-0" />
                            <h3 className="mt-3 text-sm font-semibold text-ink">{column.title}</h3>
                            <p className="mt-1 text-sm text-muted">{column.body}</p>
                        </li>
                    ))}
                </ul>
            </PageContainer>
        </section>
    );
}

export function StaffingConversionBannerSection({ bannerImageUrl }: { bannerImageUrl?: string | null }) {
    return (
        <section className="relative overflow-hidden bg-brand py-10 text-white md:py-14">
            {bannerImageUrl ? (
                <img
                    src={bannerImageUrl}
                    alt=""
                    className="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-25"
                    loading="lazy"
                />
            ) : null}
            <PageContainer className="relative min-w-0 text-center md:text-left">
                <div className="md:flex md:items-center md:justify-between md:gap-8">
                    <div className="min-w-0">
                        <h2 className="text-xl font-semibold sm:text-2xl">
                            Reliable People. Smooth Operations. Your Satisfaction.
                        </h2>
                        <p className="mt-2 max-w-xl text-sm text-white/85">
                            Let us build a stronger workforce together.
                        </p>
                    </div>
                    <div className="mt-6 md:mt-0 md:shrink-0">
                        <StaffingWorkspaceButton className="w-full md:w-auto" />
                    </div>
                </div>
            </PageContainer>
        </section>
    );
}
