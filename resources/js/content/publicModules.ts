/** Shared public module cards (Home + service pages). Icons via OurNearIcon registry names. */
export type PublicModule = {
    id: string;
    title: string;
    benefit: string;
    href: string;
    cta: string;
    icon: string;
};

export const publicModules: PublicModule[] = [
    {
        id: 'hiring',
        title: 'Hiring',
        benefit: 'Post jobs, review applicants, and move candidates through recruitment stages.',
        href: '/hire',
        cta: 'Start Hiring',
        icon: 'briefcase',
    },
    {
        id: 'talent-pool',
        title: 'Talent Pool',
        benefit: 'Discover candidates who share a public profile—human review, no automated scoring claims.',
        href: '/talent-pool',
        cta: 'Explore Talent',
        icon: 'user',
    },
    {
        id: 'jobs',
        title: 'Jobs',
        benefit: 'Search open roles, filters, and apply with your candidate profile and resume.',
        href: '/jobs',
        cta: 'Browse Jobs',
        icon: 'search',
    },
    {
        id: 'staffing',
        title: 'Staffing',
        benefit: 'Workforce and manpower requests through the employer enquiry flow.',
        href: '/staffing',
        cta: 'Request Staffing',
        icon: 'building',
    },
];

export const recruitmentPipelineStages = [
    'Applied',
    'Screening',
    'Shortlisted',
    'Interview',
    'Selected',
    'Offer',
    'Joined',
] as const;

export const productProofItems = [
    {
        title: 'Jobs board',
        body: 'Published vacancies with search, filters, and profile-based apply.',
        href: '/jobs',
        icon: 'search',
    },
    {
        title: 'Hiring workspace',
        body: 'Employer command center for jobs, applicants, and hiring services.',
        href: '/hire',
        icon: 'briefcase',
    },
    {
        title: 'Application lifecycle',
        body: 'Statuses, screening, interviews, and messaging on shared platform records.',
        href: '/services',
        icon: 'clipboard',
    },
] as const;
