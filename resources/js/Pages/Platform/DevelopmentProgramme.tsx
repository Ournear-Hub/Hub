import { Head, Link, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import Button from '@/Components/Public/Button';
import OurNearIcon from '@/icons/OurNearIcon';
import type { SharedPageProps } from '@/types/page';

type ProgrammeSlug = 'hr-recruitment' | 'business-development' | 'digital-marketing' | 'business-operations';

type ApplicationSummary = {
    programme: string;
    programme_label: string;
    status_label: string;
    can_edit: boolean;
};

type ManagedImage = {
    url: string;
    alt: string;
};

type PageProps = {
    application: ApplicationSummary | null;
    images?: Record<string, ManagedImage>;
};

const programmes: Array<{
    slug: ProgrammeSlug;
    id: string;
    icon: string;
    title: string;
    short: string;
    duties: string[];
    detail: string;
    workplace: string;
    responsibilities: string[];
    tools: string[];
    contribution: string;
    skills: string[];
    careers: string[];
}> = [
    {
        slug: 'hr-recruitment',
        id: 'hr-recruitment',
        icon: 'user',
        title: 'HR & Recruitment Intern',
        short: 'Work within our recruitment team and learn how hiring operations work in a real manpower business.',
        duties: [
            'Candidate sourcing.',
            'Résumé screening.',
            'Interview coordination.',
            'Candidate record management.',
            'Hiring support and reporting.',
        ],
        detail: 'Be part of our recruitment team and handle real hiring-related tasks that support candidate and employer requirements.',
        workplace:
            'You will work within our recruitment and talent operations team, using relevant recruitment dashboards and tools to support real recruitment processes.',
        responsibilities: [
            'Source candidates for vacancies.',
            'Screen résumés against job requirements.',
            'Coordinate interview activities.',
            'Maintain candidate records.',
            'Support hiring processes.',
            'Prepare recruitment reports.',
            'Communicate with candidates and employers as authorized.',
        ],
        tools: ['OurNear Hire', 'Applicant Tracking', 'Reports & Analytics', 'Communication Tools'],
        contribution:
            'Your work helps us find suitable talent for our client requirements and supports our recruitment business.',
        skills: [
            'Recruitment process knowledge.',
            'Communication skills.',
            'Candidate evaluation.',
            'Business coordination.',
            'Reporting and documentation.',
        ],
        careers: ['Recruiter.', 'Talent Acquisition Executive.', 'HR Executive.', 'Recruitment Consultant.'],
    },
    {
        slug: 'business-development',
        id: 'business-development',
        icon: 'search',
        title: 'Business Development & Sales Intern',
        short: 'Work on business growth activities and understand how companies discover and engage manpower service providers.',
        duties: ['Lead generation.', 'Client outreach.', 'Sales coordination.', 'Business research.', 'Employer enquiries.'],
        detail: 'Work within our business growth function and help develop employer relationships and new business opportunities.',
        workplace:
            'You will support business research, employer outreach and sales coordination while learning how a manpower consulting business develops client relationships. All outreach must follow approved company procedures and applicable privacy requirements.',
        responsibilities: [
            'Research companies and industries.',
            'Identify potential employer opportunities.',
            'Prepare company research.',
            'Support employer outreach.',
            'Record and organize business enquiries.',
            'Coordinate follow-ups.',
            'Update relevant business pipeline records.',
            'Prepare business development reports.',
        ],
        tools: ['Business Development Pipeline', 'Employer Enquiries', 'Company Research', 'Reports & Analytics'],
        contribution: 'Your work helps OurNear identify potential clients, organize employer enquiries and support business growth.',
        skills: [
            'Business research.',
            'Professional communication.',
            'Sales coordination.',
            'Lead management.',
            'Client relationship support.',
            'Business reporting.',
        ],
        careers: [
            'Business Development Executive.',
            'Sales Executive.',
            'Client Relationship Executive.',
            'Business Development Coordinator.',
        ],
    },
    {
        slug: 'digital-marketing',
        id: 'digital-marketing',
        icon: 'message',
        title: 'Digital Marketing & Content Intern',
        short: 'Be part of our digital marketing team and help promote OurNear, attract relevant audiences and support business growth.',
        duties: ['Social media management.', 'Website content.', 'Campaign support.', 'Recruitment marketing.', 'Lead tracking.'],
        detail: 'Work within our digital marketing function to attract candidates, reach employers, promote services and support business growth.',
        workplace:
            'You will contribute to the digital marketing operations of a real manpower consulting and digital manpower business, using relevant working dashboards, content workflows and marketing reports.',
        responsibilities: [
            'Plan and manage approved social media content.',
            'Promote relevant job opportunities.',
            'Support recruitment marketing.',
            'Prepare website content.',
            'Conduct basic SEO research.',
            'Support marketing campaigns.',
            'Track campaign enquiries and leads.',
            'Monitor relevant website and content performance.',
            'Prepare marketing performance reports.',
            'Coordinate with business development and recruitment teams.',
        ],
        tools: ['Marketing Campaigns Dashboard', 'Content Planning', 'Website Analytics', 'Lead Tracking', 'Reports & Analytics'],
        contribution:
            'Your work helps OurNear increase awareness, reach relevant candidates, generate employer enquiries and support the growth of OurNear Hire and other business services.',
        skills: [
            'Social media management.',
            'Content planning.',
            'Recruitment marketing.',
            'Website content.',
            'SEO fundamentals.',
            'Lead generation.',
            'Campaign analysis.',
            'Marketing reporting.',
        ],
        careers: [
            'Digital Marketing Executive.',
            'SEO Executive.',
            'Content Executive.',
            'Social Media Executive.',
            'Performance Marketing Executive.',
        ],
    },
    {
        slug: 'business-operations',
        id: 'business-operations',
        icon: 'file',
        title: 'Business Operations & MIS Intern',
        short: 'Work on operational activities, reporting and data with real business processes.',
        duties: ['Excel reporting.', 'Data management.', 'Process support.', 'Operational coordination.', 'Documentation and MIS.'],
        detail: 'Work within our business operations function and support reporting, data management, coordination and process visibility.',
        workplace:
            'You will support the day-to-day operational processes of the business using relevant reports, dashboards, spreadsheets and documentation workflows.',
        responsibilities: [
            'Prepare Excel reports.',
            'Organize and maintain authorized business data.',
            'Update relevant operational records.',
            'Support reporting workflows.',
            'Coordinate information between departments.',
            'Prepare management information reports.',
            'Document operational processes.',
            'Identify reporting inconsistencies and escalate issues.',
        ],
        tools: ['Operations Dashboard', 'Excel & Reporting', 'Data Management', 'Process Documentation', 'Reports & Analytics'],
        contribution:
            'Your work helps the organisation maintain reliable records, coordinate business activities and make informed operational decisions.',
        skills: [
            'Excel and spreadsheet reporting.',
            'Data organization.',
            'Documentation.',
            'Operational coordination.',
            'Process management.',
            'Management information systems.',
            'Business reporting.',
        ],
        careers: ['MIS Executive.', 'Operations Executive.', 'Reporting Analyst.', 'Operations Coordinator.'],
    },
];

const feeDisclosure =
    'The OurNear Development Fee is a payment towards OurNear development, not a training or course fee. This programme is designed around practical business experience, real work tasks and professional development rather than classroom-based course instruction. Applicable payment details and terms will be communicated privately to approved applicants before payment.';

function applyHref(user: boolean, programme?: ProgrammeSlug): string {
    const destination = programme
        ? `/account/development-programme?programme=${programme}`
        : '/account/development-programme';
    if (user) {
        return destination;
    }

    return `/login?intended=${encodeURIComponent(destination)}`;
}

export default function DevelopmentProgramme({ application, images = {} }: PageProps) {
    const page = usePage<SharedPageProps>();
    const user = Boolean(page.props.auth.user);
    const requested = new URLSearchParams(page.url.split('?')[1] ?? '').get('programme');
    const requestedProgramme = programmes.some((item) => item.slug === requested) ? (requested as ProgrammeSlug) : undefined;
    const [tab, setTab] = useState<ProgrammeSlug>(requestedProgramme ?? 'hr-recruitment');
    const active = programmes.find((item) => item.slug === tab) ?? programmes[0];

    return (
        <PublicLayout
            title="OurNear Development Programme | Real Work Experience"
            description="Explore the OurNear Development Programme. Gain practical experience in HR and recruitment, business development, digital marketing, or business operations, with professional records and first-job assistance."
        >
            <Head title="OurNear Development Programme | Real Work Experience" />
            <Hero user={user} application={application} programme={requestedProgramme} image={images.hero} />
            <HowItWorks />
            <ProgrammeCards
                user={user}
                onExplore={(slug) => {
                    setTab(slug);
                    window.setTimeout(() => {
                        document.getElementById(slug)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 0);
                }}
            />
            <InsideProgramme active={active} tab={tab} onTab={setTab} image={images[active.slug]} />
            <Dashboards />
            <Benefits />
            <RealWork image={images.team} />
            <WhyOurNear />
            <Journey />
            <LimitedSeats user={user} programme={requestedProgramme} />
            <FinalCta user={user} application={application} programme={requestedProgramme} />
        </PublicLayout>
    );
}

function Hero({
    user,
    application,
    programme,
    image,
}: {
    user: boolean;
    application: ApplicationSummary | null;
    programme?: ProgrammeSlug;
    image?: ManagedImage;
}) {
    return (
        <section className="border-b border-line bg-white">
            <PageContainer className="grid items-center gap-8 py-10 lg:grid-cols-[1.1fr_0.9fr] lg:py-16">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.16em] text-accent">OurNear Development Programme</p>
                    <h1 className="mt-3 text-3xl font-bold leading-tight text-brand sm:text-4xl lg:text-5xl">
                        Be a Part of a Real Company. <span className="text-accent">Gain Real Work Experience.</span>
                    </h1>
                    <p className="mt-4 max-w-xl text-base leading-relaxed text-ink">
                        Join OurNear as an Intern and work on real business tasks with our team. Build your professional record, develop practical skills and get support until you get your first job.
                    </p>
                    <ul className="mt-6 grid gap-3 sm:grid-cols-2">
                        {[
                            ['briefcase', '6 Months', 'Programme'],
                            ['building', 'Hybrid Mode', '2 Office + 3 Remote Days'],
                            ['user', '4 Career', 'Programmes'],
                            ['user', '25 Candidates', 'Per Programme'],
                        ].map(([icon, label, description]) => (
                            <li key={label} className="flex items-center gap-3 rounded-lg border border-line bg-canvas px-3 py-2">
                                <OurNearIcon name={icon} className="text-brand" />
                                <span>
                                    <span className="block text-sm font-semibold text-ink">{label}</span>
                                    <span className="block text-xs text-muted">{description}</span>
                                </span>
                            </li>
                        ))}
                    </ul>
                    <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                        <Button href={applyHref(user, programme)}>Apply Now</Button>
                        <Button href="#how-ournear-works" variant="outline">
                            See How It Works
                        </Button>
                    </div>
                    {application ? <ApplicationStatus application={application} /> : null}
                    <p className="mt-3 text-xs text-muted">Login required to apply. Applying does not activate the programme.</p>
                </div>
                <ManagedPhoto image={image} fallback={<WorkplaceVisual />} className="min-h-[18rem] rounded-2xl" />
            </PageContainer>
        </section>
    );
}

function ManagedPhoto({
    image,
    fallback = null,
    className = '',
}: {
    image?: ManagedImage;
    fallback?: ReactNode;
    className?: string;
}) {
    const [failed, setFailed] = useState(false);
    if (!image?.url || failed) {
        return fallback;
    }

    return (
        <img
            src={image.url}
            alt={image.alt}
            className={`w-full bg-canvas object-cover ${className}`}
            onError={() => setFailed(true)}
        />
    );
}

function WorkplaceVisual() {
    return (
        <div className="relative overflow-hidden rounded-2xl border border-line bg-brand text-white">
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(241,90,36,0.35),transparent_45%)]" aria-hidden="true" />
            <div className="relative grid min-h-[18rem] content-end gap-4 p-6" role="img" aria-label="Illustration of colleagues working together around a laptop in an office">
                <p className="text-[10px] font-medium uppercase tracking-wide text-white/70">Illustrative workplace scene</p>
                <div className="grid grid-cols-3 gap-3">
                    {['Brief', 'Pipeline', 'Report'].map((label) => (
                        <div key={label} className="rounded-lg bg-white/10 p-3">
                            <div className="mb-2 h-2 w-10 rounded bg-accent" />
                            <p className="text-xs font-semibold">{label}</p>
                            <p className="mt-1 text-[10px] text-white/70">Sample workspace</p>
                        </div>
                    ))}
                </div>
                <div className="absolute right-4 top-4 max-w-[12rem] rounded-xl bg-white p-3 text-ink shadow-sm">
                    <p className="text-xs font-semibold">Start Your Professional Journey</p>
                    <p className="mt-1 text-[11px] text-muted">Work as an OurNear Intern</p>
                </div>
            </div>
        </div>
    );
}

function HowItWorks() {
    const departments = [
        ['message', 'Digital Marketing & Content', 'Creates awareness and attracts relevant audiences.', ['Reach candidates and employers.', 'Website content.', 'Marketing campaigns.', 'Content publishing.', 'Lead generation.']],
        ['search', 'Business Development & Sales', 'Develops employer relationships and business opportunities.', ['Generate business opportunities.', 'Employer enquiries.', 'Client outreach.', 'Sales coordination.', 'Business research.']],
        ['user', 'HR & Recruitment', 'Connects suitable candidates with employer requirements.', ['Manage hiring operations.', 'Candidate sourcing.', 'Résumé screening.', 'Interview coordination.', 'Recruitment processes.']],
        ['file', 'Business Operations & MIS', 'Supports coordination, reporting and operational visibility across departments.', ['Reporting.', 'Data management.', 'Process support.', 'Operational coordination.', 'Management information.']],
    ] as const;

    return (
        <section id="how-ournear-works" className="bg-canvas py-12">
            <PageContainer>
                <h2 className="text-2xl font-bold text-brand sm:text-3xl">How OurNear Works</h2>
                <p className="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
                    Our departments work together to deliver recruitment, staffing and manpower solutions. As an OurNear Intern, you will work within one department and contribute to real business activities.
                </p>
                <div className="mx-auto mt-8 max-w-xl rounded-2xl bg-brand p-5 text-center text-white">
                    <OurNearIcon name="building" className="mx-auto text-accent" />
                    <p className="mt-2 text-lg font-bold">OurNear</p>
                    <p className="text-sm">Manpower Consulting & Digital Manpower Business</p>
                    <p className="mt-1 text-xs text-white/80">13+ Years of Industry Experience</p>
                </div>
                <div className="mx-auto my-3 h-6 w-px bg-brand/40" aria-hidden="true" />
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {departments.map(([icon, title, contribution, duties]) => (
                        <article key={title} className="rounded-xl border border-line bg-white p-4">
                            <OurNearIcon name={icon} className="text-accent" />
                            <h3 className="mt-2 text-sm font-semibold text-ink">{title}</h3>
                            <ul className="mt-2 space-y-1 text-xs text-muted">
                                {duties.map((duty) => (
                                    <li key={duty}>{duty}</li>
                                ))}
                            </ul>
                            <p className="mt-3 text-xs font-medium text-brand">{contribution}</p>
                        </article>
                    ))}
                </div>
                <div className="mt-4 flex flex-wrap items-center justify-center gap-2 text-xs font-medium text-brand" aria-label="Departments collaborate with each other">
                    {['Digital Marketing', 'Business Development', 'HR & Recruitment', 'Business Operations'].map((name, index, list) => (
                        <span key={name} className="inline-flex items-center gap-2">
                            {name}
                            {index < list.length - 1 ? <OurNearIcon name="arrow-right" className="text-accent" size="sm" /> : null}
                        </span>
                    ))}
                </div>
                <p className="mt-3 text-center text-xs text-muted">
                    The four designations are distinct roles in one organisation. They exchange information and collaborate. None manages the others.
                </p>
            </PageContainer>
        </section>
    );
}

function ProgrammeCards({ user, onExplore }: { user: boolean; onExplore: (slug: ProgrammeSlug) => void }) {
    return (
        <section className="bg-white py-12">
            <PageContainer>
                <h2 className="text-2xl font-bold text-brand sm:text-3xl">Our 4 Career Programmes</h2>
                <p className="mt-2 max-w-3xl text-sm text-muted">
                    Choose the programme that matches your interests. Each programme has limited seats to ensure quality work experience, meaningful responsibilities and career support.
                </p>
                <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {programmes.map((programme) => (
                        <article key={programme.slug} className="flex flex-col rounded-xl border border-line p-4">
                            <OurNearIcon name={programme.icon} className="text-brand" />
                            <h3 className="mt-2 text-base font-semibold text-ink">{programme.title}</h3>
                            <p className="mt-2 text-sm text-muted">{programme.short}</p>
                            <ul className="mt-3 flex-1 space-y-1 text-xs text-ink">
                                {programme.duties.map((duty) => (
                                    <li key={duty}>{duty}</li>
                                ))}
                            </ul>
                            <p className="mt-3 inline-flex w-fit rounded-full bg-accent/10 px-2 py-1 text-xs font-semibold text-accent">25 Seats</p>
                            <a
                                href={`#${programme.id}`}
                                className="mt-3 text-sm font-semibold text-brand underline-offset-2 hover:underline"
                                onClick={(event) => {
                                    event.preventDefault();
                                    onExplore(programme.slug);
                                }}
                            >
                                Explore Programme
                            </a>
                            <Button href={applyHref(user, programme.slug)} className="mt-3" size="sm">
                                Apply Now
                            </Button>
                        </article>
                    ))}
                </div>
            </PageContainer>
        </section>
    );
}

function InsideProgramme({
    active,
    tab,
    onTab,
    image,
}: {
    active: (typeof programmes)[number];
    tab: ProgrammeSlug;
    onTab: (slug: ProgrammeSlug) => void;
    image?: ManagedImage;
}) {
    return (
        <section className="bg-canvas py-12">
            <PageContainer>
                <h2 className="text-2xl font-bold text-brand sm:text-3xl">Inside Each Programme</h2>
                <p className="mt-2 max-w-3xl text-sm text-muted">See what you will do, where you will work and how your experience can support your career.</p>
                <div className="mt-6 flex gap-2 overflow-x-auto pb-2" role="tablist" aria-label="Career programmes">
                    {programmes.map((programme) => (
                        <button
                            key={programme.slug}
                            type="button"
                            role="tab"
                            id={`tab-${programme.slug}`}
                            aria-selected={tab === programme.slug}
                            aria-controls={programme.id}
                            className={`shrink-0 rounded-full px-3 py-2 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent ${tab === programme.slug ? 'bg-brand text-white' : 'bg-white text-brand'}`}
                            onClick={() => onTab(programme.slug)}
                        >
                            {programme.title.replace(' Intern', '')}
                        </button>
                    ))}
                </div>
                <article id={active.id} role="tabpanel" aria-labelledby={`tab-${active.slug}`} className="mt-4 rounded-2xl border border-line bg-white p-5">
                    <h3 className="text-xl font-bold text-brand">{active.title}</h3>
                    <p className="mt-2 text-sm text-muted">{active.detail}</p>
                    <div className="mt-6 grid gap-6 lg:grid-cols-2">
                        <div>
                            <h4 className="font-semibold text-ink">Where You Will Work</h4>
                            <p className="mt-2 text-sm text-muted">{active.workplace}</p>
                            <ManagedPhoto image={image} className="mt-4 aspect-[4/3] rounded-xl" />
                        </div>
                        <div>
                            <h4 className="font-semibold text-ink">Your Responsibilities</h4>
                            <ul className="mt-2 space-y-1 text-sm text-ink">
                                {active.responsibilities.map((item) => (
                                    <li key={item} className="flex gap-2">
                                        <OurNearIcon name="check" className="mt-0.5 shrink-0 text-accent" size="sm" />
                                        <span>{item}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                    <h4 className="mt-6 font-semibold text-ink">Tools & Dashboards</h4>
                    <ul className="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {active.tools.map((tool) => (
                            <li key={tool} className="rounded-lg border border-line px-3 py-2 text-sm">
                                {tool}
                            </li>
                        ))}
                    </ul>
                    <div className="mt-6 grid gap-3 lg:grid-cols-3">
                        <InfoCard title="How You Contribute to OurNear" body={active.contribution} />
                        <InfoCard title="Skills You Will Develop" items={active.skills} />
                        <InfoCard title="Future Career Opportunities" items={active.careers} note="Potential career directions, not guaranteed employment offers." />
                    </div>
                </article>
                <FeeNote />
            </PageContainer>
        </section>
    );
}

function InfoCard({ title, body, items, note }: { title: string; body?: string; items?: string[]; note?: string }) {
    return (
        <div className="rounded-xl border border-line bg-canvas p-4">
            <h5 className="text-sm font-semibold text-brand">{title}</h5>
            {body ? <p className="mt-2 text-sm text-ink">{body}</p> : null}
            {items ? (
                <ul className="mt-2 space-y-1 text-sm text-ink">
                    {items.map((item) => (
                        <li key={item}>{item}</li>
                    ))}
                </ul>
            ) : null}
            {note ? <p className="mt-2 text-xs text-muted">{note}</p> : null}
        </div>
    );
}

function Dashboards() {
    const cards = [
        ['Recruitment Dashboard', 'Understand how vacancies, candidate records and recruitment activities are managed.', 'Vacancy A-Sample'],
        ['Business Development Pipeline', 'See how employer enquiries, business opportunities and follow-up activities are organized.', 'Enquiry sample'],
        ['Marketing Campaigns Dashboard', 'Understand how marketing activities, campaign results and incoming leads are tracked.', 'Campaign sample'],
        ['Operations & MIS Dashboard', 'Learn how reporting, business data and operational information support decision-making.', 'Report sample'],
    ];

    return (
        <section className="bg-white py-12">
            <PageContainer>
                <h2 className="text-2xl font-bold text-brand sm:text-3xl">Work with Real Systems and Dashboards</h2>
                <p className="mt-2 max-w-3xl text-sm text-muted">
                    You will get access to our working dashboards to understand how digital manpower business operations are managed. Access is granted only after authorization. These previews use fictional sample labels.
                </p>
                <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {cards.map(([title, description, sample]) => (
                        <article key={title} className="rounded-xl border border-line p-4">
                            <div className="rounded-lg bg-brand/5 p-3" aria-hidden="true">
                                <p className="text-[10px] font-semibold uppercase text-muted">Illustrative sample</p>
                                <p className="mt-2 text-sm font-semibold text-ink">{sample}</p>
                                <div className="mt-2 h-2 rounded bg-line">
                                    <div className="h-2 w-1/2 rounded bg-accent" />
                                </div>
                            </div>
                            <h3 className="mt-3 text-sm font-semibold text-ink">{title}</h3>
                            <p className="mt-1 text-xs text-muted">{description}</p>
                        </article>
                    ))}
                </div>
            </PageContainer>
        </section>
    );
}

function Benefits() {
    const items = [
        ['briefcase', 'Experience Certificate', 'Get an OurNear Intern experience certificate after meeting the programme completion requirements.'],
        ['user', 'Digital Presence', 'Build a professional digital presence identifying your OurNear Intern role, subject to consent and applicable programme terms.'],
        ['search', 'Performance Record', 'Your work performance, evaluations and documented achievements can become part of your professional record.'],
        ['file', 'Résumé Support', 'Present your OurNear Intern role, responsibilities and relevant work experience clearly on your résumé.'],
        ['user', 'Professional References', 'Eligible participants may receive professional references based on their actual work, conduct and performance.'],
        ['briefcase', 'Job Assistance', 'We will assist with your job search, share your résumé with relevant recruiters with your consent, and support you until you secure your first job under the programme terms.'],
    ];

    return (
        <section className="bg-canvas py-12">
            <PageContainer>
                <h2 className="text-2xl font-bold text-brand sm:text-3xl">What You Will Get as an OurNear Intern</h2>
                <p className="mt-2 text-sm text-muted">More than an internship — a professional work experience and career support opportunity.</p>
                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    {items.map(([icon, title, description]) => (
                        <article key={title} className="rounded-xl border border-line bg-white p-4">
                            <OurNearIcon name={icon} className="text-accent" />
                            <h3 className="mt-2 text-sm font-semibold text-ink">{title}</h3>
                            <p className="mt-1 text-xs leading-relaxed text-muted">{description}</p>
                        </article>
                    ))}
                </div>
                <p className="mt-4 text-xs text-muted">
                    These outcomes follow programme policy. They are not a guaranteed job, salary, stipend, reference, or certificate, and payment does not place a participant in a job.
                </p>
            </PageContainer>
        </section>
    );
}

function RealWork({ image }: { image?: ManagedImage }) {
    return (
        <section className="bg-white py-12">
            <PageContainer className="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <ManagedPhoto image={image} className="aspect-[16/10] rounded-2xl lg:col-span-2" />
                <div className="rounded-2xl bg-brand p-6 text-white">
                    <h2 className="text-2xl font-bold">Work Like a Real Employee</h2>
                    <p className="mt-3 text-sm leading-relaxed text-white/90">
                        You will work on real business tasks, take responsibility, coordinate with the team and have your performance evaluated — just like a member of our company team.
                    </p>
                    <ul className="mt-4 space-y-1 text-sm">
                        {[
                            'Work within your selected department.',
                            'Use relevant working systems and dashboards.',
                            'Follow defined work processes.',
                            'Coordinate with colleagues.',
                            'Complete assigned tasks.',
                            'Maintain professional work records.',
                            'Receive performance feedback.',
                        ].map((item) => (
                            <li key={item}>{item}</li>
                        ))}
                    </ul>
                    <Button href="#how-ournear-works" variant="outline" className="mt-5">
                        See How It Works
                    </Button>
                </div>
                <div className="rounded-2xl border border-line bg-canvas p-6">
                    <OurNearIcon name="building" className="text-brand" />
                    <h3 className="mt-3 text-lg font-semibold text-brand">We are a manpower consulting and digital manpower business, not a training institute.</h3>
                    <p className="mt-2 text-sm text-muted">
                        With 13+ years of industry experience, we provide a practical business environment where selected participants can contribute to real work and build professional experience.
                    </p>
                </div>
            </PageContainer>
        </section>
    );
}

function WhyOurNear() {
    const items = [
        ['clipboard', '13+ Years', 'Industry Experience'],
        ['user', 'Real Business', 'Operations'],
        ['home', 'Working', 'Dashboards'],
        ['building', 'Connecting Talent', 'With Employers'],
    ];

    return (
        <section className="border-y border-line bg-canvas py-10">
            <PageContainer>
                <h2 className="text-2xl font-bold text-brand">Why OurNear?</h2>
                <p className="mt-2 max-w-3xl text-sm text-muted">
                    We are a manpower consultant with more than 13 years of industry experience and expertise in digital manpower business operations.
                </p>
                <ul className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {items.map(([icon, title, description]) => (
                        <li key={title} className="flex items-center gap-3">
                            <OurNearIcon name={icon} className="text-accent" />
                            <span>
                                <span className="block text-sm font-semibold text-ink">{title}</span>
                                <span className="block text-xs text-muted">{description}</span>
                            </span>
                        </li>
                    ))}
                </ul>
            </PageContainer>
        </section>
    );
}

function Journey() {
    const steps = [
        ['user', 'Login / Register', 'Create your account or log in as a candidate.'],
        ['file', 'Apply', 'Complete the Development Programme application form.'],
        ['search', 'Review', 'Our team will review your application.'],
        ['message', 'Get Details', 'Approved candidates will receive their OurNear Development Fee details privately.'],
        ['check', 'Confirm & Join', 'Complete the applicable payment process and receive programme access after the required conditions are satisfied.'],
        ['user', 'Start Working', 'Begin your six-month programme and work with our team.'],
    ];

    return (
        <section className="bg-white py-12">
            <PageContainer>
                <h2 className="text-2xl font-bold text-brand sm:text-3xl">Your Journey with OurNear</h2>
                <p className="mt-2 text-sm text-muted">A simple process to join and start your career development journey.</p>
                <ol className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    {steps.map(([icon, title, description], index) => (
                        <li key={title} className="rounded-xl border border-line p-4">
                            <p className="text-xs font-semibold text-accent">Step {index + 1}</p>
                            <OurNearIcon name={icon} className="mt-2 text-brand" />
                            <h3 className="mt-2 text-sm font-semibold text-ink">{title}</h3>
                            <p className="mt-1 text-xs text-muted">{description}</p>
                        </li>
                    ))}
                </ol>
                <FeeNote />
            </PageContainer>
        </section>
    );
}

function LimitedSeats({ user, programme }: { user: boolean; programme?: ProgrammeSlug }) {
    return (
        <section className="bg-accent/5 py-10">
            <PageContainer className="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 className="text-2xl font-bold text-brand">Limited Seats — 25 Candidates Per Programme</h2>
                    <p className="mt-2 max-w-2xl text-sm text-muted">
                        We plan to select up to 25 candidates for each career programme to support meaningful work experience, individual performance evaluation and career development.
                    </p>
                    <p className="mt-2 text-sm font-semibold text-ink">4 Programmes | 25 Candidates Per Programme | 100 Planned Seats</p>
                </div>
                <div>
                    <Button href={applyHref(user, programme)}>Apply Now</Button>
                    <p className="mt-2 text-xs text-muted">Login required to apply</p>
                </div>
            </PageContainer>
        </section>
    );
}

function FinalCta({
    user,
    application,
    programme,
}: {
    user: boolean;
    application: ApplicationSummary | null;
    programme?: ProgrammeSlug;
}) {
    return (
        <section className="bg-brand py-12 text-white">
            <PageContainer>
                <h2 className="text-2xl font-bold">Apply for the Development Programme</h2>
                <p className="mt-2 max-w-2xl text-sm text-white/85">
                    Use your OurNear Hire candidate account. Our team reviews each application before any payment discussion or programme access.
                </p>
                <Button href={applyHref(user, programme)} className="mt-5">
                    Apply Now
                </Button>
                {application ? <ApplicationStatus application={application} light /> : null}
            </PageContainer>
        </section>
    );
}

function ApplicationStatus({ application, light = false }: { application: ApplicationSummary; light?: boolean }) {
    return (
        <p className={`mt-3 text-sm ${light ? 'text-white' : 'text-ink'}`}>
            Your application: {application.programme_label} — {application.status_label}.{' '}
            <Link href="/account/development-programme" className="font-semibold underline">
                View submitted application
            </Link>
        </p>
    );
}

function FeeNote() {
    return (
        <aside className="mt-6 rounded-xl border border-line bg-white p-4">
            <h3 className="text-sm font-semibold text-brand">OurNear Development Fee</h3>
            <p className="mt-2 text-sm leading-relaxed text-muted">{feeDisclosure}</p>
        </aside>
    );
}
