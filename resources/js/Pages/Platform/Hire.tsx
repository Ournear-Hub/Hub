import { usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import {
    HireAtsSection,
    HireCompanyAndReportsSection,
    HireHeroSection,
    HireHowItWorksSection,
    HireNotificationsSection,
    HireScreeningDemoSection,
} from '@/Components/Public/HiringLanding/HireLandingSections';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { HirePresentation } from '@/types/hireLanding';
import type { SharedPageProps } from '@/types/page';
import type { SiteContent } from '@/types/public';
import { resolveHiringWorkspaceHref, resolvePostJobHref } from '@/utils/employerHiringEntry';

type HireProps = {
    content: SiteContent;
    cms?: CmsPage;
    sampleShareUrl: string;
    hirePresentation: HirePresentation;
};

export default function Hire({ content, cms, sampleShareUrl, hirePresentation }: HireProps) {
    const page = usePage<SharedPageProps>().props;
    const user = page.auth.user;
    const postJobHref = resolvePostJobHref(user);
    const hero = cmsSection(cms, 'hero');
    const companyPreview = cmsSection(cms, 'solution');
    const workspaceHrefs = {
        createJob: postJobHref,
        applications: resolveHiringWorkspaceHref(user, '/account/hiring/applications'),
        pipeline: resolveHiringWorkspaceHref(user, '/account/hiring'),
        companyProfile: resolveHiringWorkspaceHref(user, '/account/company'),
    };

    return (
        <PublicLayout title="Hiring" description={content.tagline ?? undefined}>
            <HireHeroSection
                postJobHref={postJobHref}
                workspaceHrefs={workspaceHrefs}
                presentation={hirePresentation}
                heroImageUrl={hero?.image_url}
            />
            <HireHowItWorksSection postJobHref={postJobHref} />
            <HireScreeningDemoSection />
            <HireAtsSection presentation={hirePresentation} />
            <HireNotificationsSection
                communicationSettingsHref={resolveHiringWorkspaceHref(user, '/account/settings/verification')}
            />
            <HireCompanyAndReportsSection
                presentation={hirePresentation}
                previewImageUrl={companyPreview?.image_url}
                companyProfileHref={workspaceHrefs.companyProfile}
            />
        </PublicLayout>
    );
}
