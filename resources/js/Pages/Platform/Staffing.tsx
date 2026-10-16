import ShowcaseCompanyGrid, { type ShowcaseCompanyPublic } from '@/Components/Public/ShowcaseCompanyGrid';
import PublicLayout from '@/Layouts/PublicLayout';
import {
    StaffingConversionBannerSection,
    StaffingCtaProvider,
    StaffingExpectSection,
    StaffingHeroSection,
    StaffingProcessSection,
    StaffingRequirementSection,
    StaffingResponsibilitySection,
    StaffingWorkforcePartnerSection,
} from '@/Components/Public/Staffing/StaffingLandingSections';
import { staffingSectionImageConfig } from '@/lib/staffingCmsImages';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { SharedPageProps } from '@/types/page';
import type { SiteContent } from '@/types/public';
import {
    hasCompanyProfile,
    hiringActivationUrl,
    STAFFING_WORKSPACE_PATH,
} from '@/utils/employerHiringEntry';
import { staffingWorkspaceHref } from '@/utils/accountDashboardEntry';
import { usePage } from '@inertiajs/react';

type StaffingProps = {
    content: SiteContent;
    cms?: CmsPage;
    staffing_form: {
        industries: string[];
        workforce_types: string[];
    };
    staffingShowcaseCompanies?: ShowcaseCompanyPublic[];
};

export default function Staffing({ content, cms, staffing_form, staffingShowcaseCompanies = [] }: StaffingProps) {
    const { auth, flash } = usePage<SharedPageProps>().props;
    const user = auth.user;
    const hero = cmsSection(cms, 'hero');
    const partnerSection = cmsSection(cms, 'workforce_partner');
    const cta = cmsSection(cms, 'cta');

    const workspaceHref = staffingWorkspaceHref(user);
    const registerCompanyHref = hiringActivationUrl(STAFFING_WORKSPACE_PATH);
    const showRegisterCompany = !hasCompanyProfile(user);

    const heroImage = staffingSectionImageConfig(hero);
    const partnerImage = staffingSectionImageConfig(partnerSection);

    return (
        <PublicLayout title="Staffing" description={content.tagline ?? undefined}>
            <StaffingCtaProvider
                workspaceHref={workspaceHref}
                registerCompanyHref={registerCompanyHref}
                showRegisterCompany={showRegisterCompany}
            >
                <StaffingHeroSection heroImage={heroImage} />
                <StaffingProcessSection />
                <StaffingWorkforcePartnerSection partnerImage={partnerImage} />
                <StaffingResponsibilitySection />
                <StaffingExpectSection />
                <ShowcaseCompanyGrid
                    title="Staffing Companies"
                    companies={staffingShowcaseCompanies}
                    id="staffing-companies-heading"
                />
                <StaffingRequirementSection
                    industries={staffing_form.industries}
                    workforceTypes={staffing_form.workforce_types}
                    successMessage={flash.success}
                />
                <StaffingConversionBannerSection bannerImageUrl={cta?.image_url} />
            </StaffingCtaProvider>
        </PublicLayout>
    );
}
