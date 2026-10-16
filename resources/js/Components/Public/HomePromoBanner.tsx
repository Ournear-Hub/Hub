import { usePage } from '@inertiajs/react';
import Button from '@/Components/atoms/Button';
import PageContainer from '@/Components/Public/PageContainer';
import type { SharedPageProps } from '@/types/page';
import { resolveHiringWorkspaceHref } from '@/utils/hiringWorkspaceEntry';
import { resolvePublicCmsHref } from '@/utils/employerHiringEntry';

export type HomePromoBannerData = {
    eyebrow?: string | null;
    heading: string;
    body: string | null;
    primary_label: string | null;
    primary_href: string | null;
    image_url: string | null;
};

type Props = {
    banner: HomePromoBannerData;
};

export default function HomePromoBanner({ banner }: Props) {
    const user = usePage<SharedPageProps>().props.auth.user;
    const ctaHref =
        resolvePublicCmsHref(user, banner.primary_href) ?? resolveHiringWorkspaceHref(user);
    const ctaLabel = banner.primary_label ?? 'Explore ATS';
    const eyebrow = banner.eyebrow ?? 'ONE PLATFORM. EVERY HIRING NEED.';
    const heading = banner.heading || 'Smarter Hiring. Better Teams.';
    const body =
        banner.body ??
        'Use OurNear Hire to post jobs, manage talent and coordinate recruitment through one connected platform.';

    return (
        <section className="border-t border-line bg-brand py-7 text-white md:py-8">
            <PageContainer className="flex min-w-0 flex-col gap-5 md:flex-row md:items-center md:gap-8">
                <div className="min-w-0 flex-1">
                    <p className="text-xs font-semibold uppercase tracking-wide text-accent">{eyebrow}</p>
                    <h2 className="mt-1 text-xl font-semibold sm:text-2xl">{heading}</h2>
                    <p className="mt-2 max-w-2xl text-sm leading-relaxed text-white/85">{body}</p>
                    {ctaLabel && ctaHref ? (
                        <div className="mt-4">
                            <Button surface="public" href={ctaHref} variant="primary" className="w-full sm:w-auto">
                                {ctaLabel}
                            </Button>
                        </div>
                    ) : null}
                </div>
                {banner.image_url ? (
                    <div className="relative mx-auto w-full max-w-xs shrink-0 overflow-hidden rounded-lg border border-white/15 md:max-w-[14rem]">
                        <img src={banner.image_url} alt="" className="aspect-[4/3] w-full object-cover" loading="lazy" />
                    </div>
                ) : null}
            </PageContainer>
        </section>
    );
}
