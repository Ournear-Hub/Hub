import { usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

type BrandMarkProps = {
    className?: string;
    inverted?: boolean;
    usePublicBranding?: boolean;
};

export default function BrandMark({ className = '', inverted = false, usePublicBranding = false }: BrandMarkProps) {
    const branding = usePage<SharedPageProps>().props.branding;
    const name = branding?.website_name ?? '';
    const logoUrl = usePublicBranding ? branding?.logo_url ?? null : null;
    const iconUrl = usePublicBranding ? branding?.icon_url ?? null : null;

    if (logoUrl) {
        return (
            <img
                src={logoUrl}
                alt={name}
                className={`h-8 w-auto max-h-10 max-w-[200px] object-contain object-left ${className}`}
            />
        );
    }

    if (iconUrl) {
        return (
            <span className={`inline-flex items-center gap-2 font-semibold tracking-tight ${className}`}>
                <img src={iconUrl} alt="" className="h-8 w-8 object-contain" />
                <span className={inverted ? 'text-white' : 'text-brand'}>{name}</span>
            </span>
        );
    }

    if (usePublicBranding && name !== 'OurNear Talent') {
        return (
            <span className={`inline-flex items-baseline gap-1 font-semibold tracking-tight ${className}`}>
                <span className={inverted ? 'text-white' : 'text-brand'}>{name}</span>
            </span>
        );
    }

    return (
        <span className={`inline-flex items-baseline gap-1 font-semibold tracking-tight ${className}`}>
            <span className={inverted ? 'text-white' : 'text-brand'}>OurNear</span>
            <span className={inverted ? 'text-white/90' : 'text-ink'}>Talent</span>
        </span>
    );
}
