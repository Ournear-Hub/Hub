import type { AudienceFeatureCard } from '@/Components/Public/HomeAudienceFeatureCards';

export const defaultHiringFeatures: AudienceFeatureCard[] = [
    {
        title: 'Post a Job',
        body: 'Publish roles and reach candidates on the OurNear jobs board.',
        icon: 'briefcase',
        href: '/register',
    },
    {
        title: 'Search Talent',
        body: 'Explore public candidate profiles on Talent Pool.',
        icon: 'search',
        href: '/talent-pool',
    },
    {
        title: 'Manage Applications',
        body: 'Review applicants and advance records through ATS stages.',
        icon: 'clipboard',
        href: '/account/hiring/applications',
    },
    {
        title: 'Interview & Schedule',
        body: 'Coordinate interviews from your applications workspace.',
        icon: 'message',
        href: '/account/hiring/applications',
    },
];

export const defaultCandidateFeatures: AudienceFeatureCard[] = [
    {
        title: 'Find Jobs',
        body: 'Search live openings with keyword and location filters.',
        icon: 'search',
        href: '/jobs',
    },
    {
        title: 'Create Profile',
        body: 'Set up your candidate profile to apply with one account.',
        icon: 'user',
        href: '/register',
    },
    {
        title: 'Upload Resume',
        body: 'Keep resumes in your library for faster applications.',
        icon: 'upload',
        href: '/account/resumes',
    },
    {
        title: 'Internships',
        body: 'Internship roles appear on the jobs board when employers publish them.',
        icon: 'briefcase',
        href: null,
    },
];
