import { useEffect } from 'react';
import {
    TalentPoolDifferenceSection,
    TalentPoolFinalCtaSection,
    TalentPoolHeroSection,
    TalentPoolIndustriesSection,
    TalentPoolManualWorkSection,
    TalentPoolProcessSection,
    TalentPoolWhySection,
} from '@/Components/Public/TalentPool/TalentPoolLandingSections';
import ShowcaseCompanyGrid, { type ShowcaseCompanyPublic } from '@/Components/Public/ShowcaseCompanyGrid';
import PublicLayout from '@/Layouts/PublicLayout';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { SiteContent } from '@/types/public';
import type { SharedPageProps } from '@/types/page';
import { usePage } from '@inertiajs/react';
import {
    resolveTalentPoolRequirementHref,
    scrollToTalentPoolProcess,
    TALENT_POOL_PROCESS_SECTION_ID,
} from '@/utils/employerTalentPoolEntry';

type TalentPoolProps = {
    content: SiteContent;
    cms?: CmsPage;
    industries: string[];
    talentPoolShowcaseCompanies?: ShowcaseCompanyPublic[];
};

export default function TalentPool({ content, cms, industries, talentPoolShowcaseCompanies = [] }: TalentPoolProps) {
    const user = usePage<SharedPageProps>().props.auth.user;
    const hero = cmsSection(cms, 'hero');
    const manualWork = cmsSection(cms, 'manual_work') ?? cmsSection(cms, 'how_it_works');
    const cta = cmsSection(cms, 'cta');

    const requirementHref = resolveTalentPoolRequirementHref(user);
    const heroAlt = hero?.extra?.image_alt ?? hero?.subtitle ?? null;
    const manualAlt = manualWork?.extra?.image_alt ?? manualWork?.subtitle ?? null;

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        if (window.location.hash === `#${TALENT_POOL_PROCESS_SECTION_ID}`) {
            scrollToTalentPoolProcess();
        }
    }, []);

    return (
        <PublicLayout title="Talent Pool" description={content.tagline ?? undefined}>
            <TalentPoolHeroSection
                requirementHref={requirementHref}
                heroImageUrl={hero?.image_url}
                heroImageAlt={heroAlt}
                heroBody={hero?.body}
            />
            <ShowcaseCompanyGrid
                title="Talent Pool Companies"
                companies={talentPoolShowcaseCompanies}
                id="talent-pool-companies-heading"
            />
            <TalentPoolDifferenceSection />
            <TalentPoolProcessSection />
            <TalentPoolManualWorkSection
                sectionImageUrl={manualWork?.image_url}
                sectionImageAlt={manualAlt}
                sectionBody={manualWork?.body}
            />
            <TalentPoolWhySection />
            <TalentPoolIndustriesSection industries={industries} />
            <TalentPoolFinalCtaSection
                requirementHref={requirementHref}
                ctaImageUrl={cta?.image_url}
                ctaBody={cta?.body}
            />
        </PublicLayout>
    );
}
