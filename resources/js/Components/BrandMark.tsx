import { usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

type BrandMarkProps = {
    className?: string;
    inverted?: boolean;
    usePublicBranding?: boolean;
    surface?: 'product' | 'admin';
};

/** Public hire product wordmark: platform "OurNear" + module "Hire" (legacy CMS name supported). */
function usesHireProductWordmark(websiteName: string): boolean {
    const name = websiteName.trim();

    return name === 'OurNear' || name === 'OurNear Talent' || name === 'OurNear Hire';
}

export default function BrandMark({ className = '', inverted = false, usePublicBranding = false, surface = 'product' }: BrandMarkProps) {
    const branding = usePage<SharedPageProps>().props.branding;
    const name = branding?.website_name?.trim() || 'OurNear';
    const logoUrl = usePublicBranding ? branding?.logo_url ?? null : null;
    const wordmarkUrl = usePublicBranding ? branding?.wordmark_url ?? null : null;
    const iconUrl = usePublicBranding ? branding?.icon_url ?? null : null;
    const tone = inverted ? 'text-white' : 'text-brand';

    if (logoUrl && wordmarkUrl) {
        return (
            <span className={`inline-flex items-center gap-2 ${className}`}>
                <img src={logoUrl} alt="" className="h-8 w-8 object-contain" />
                <img src={wordmarkUrl} alt={name} className="h-8 w-auto max-w-[180px] object-contain object-left" />
            </span>
        );
    }

    if (logoUrl) {
        return (
            <span className={`inline-flex items-center gap-2 ${className}`}>
                <img src={logoUrl} alt={name} className="h-8 w-auto max-h-10 max-w-[200px] object-contain object-left" />
            </span>
        );
    }

    return (
        <span className={`inline-flex items-center gap-2 font-semibold tracking-tight ${className}`}>
            {iconUrl ? <img src={iconUrl} alt="" className="h-8 w-8 object-contain" /> : null}
            <span className={tone}>{usesHireProductWordmark(name) ? 'OurNear' : name}</span>
            {surface === 'product' && usesHireProductWordmark(name) ? (
                <span className={inverted ? 'text-white/90' : 'text-ink'}>Hire</span>
            ) : null}
        </span>
    );
}
