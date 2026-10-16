import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    resolveHireWordmarkUrl,
    resolvePublicWordmarkUrl,
    type PublicBrandingProps,
} from '@/lib/brandAssets';
import type { SharedPageProps } from '@/types/page';

type BrandMarkProps = {
    className?: string;
    inverted?: boolean;
    usePublicBranding?: boolean;
    logosOnly?: boolean;
    placement?: 'header' | 'footer';
    surface?: 'product' | 'admin';
    compact?: boolean;
};

function ConfiguredLogoImage({
    src,
    alt,
    className,
    width,
    height,
}: {
    src: string;
    alt: string;
    className: string;
    width: number;
    height: number;
}) {
    const [failed, setFailed] = useState(false);

    if (!src || failed) {
        return null;
    }

    return (
        <img
            src={src}
            alt={alt}
            className={className}
            width={width}
            height={height}
            onError={() => setFailed(true)}
        />
    );
}

function PublicSiteLogo({
    branding,
    placement,
    compact,
    className,
    websiteName,
}: {
    branding?: PublicBrandingProps | null;
    placement: 'header' | 'footer';
    compact: boolean;
    className: string;
    websiteName: string;
}) {
    const src =
        placement === 'footer'
            ? branding?.footer_logo_url?.trim() || ''
            : branding?.header_logo_url?.trim() || '';

    const horizontalClass = compact
        ? 'h-8 w-auto max-w-[12rem] shrink-0 object-contain object-left'
        : 'h-9 w-auto max-w-[14rem] shrink-0 object-contain object-left sm:h-10 sm:max-w-[16rem]';

    if (!src) {
        return <span className={`inline-block min-h-9 shrink-0 ${className}`} aria-hidden="true" />;
    }

    return (
        <span className={`inline-flex max-w-full shrink-0 items-center ${className}`}>
            <ConfiguredLogoImage
                src={src}
                alt={websiteName}
                className={horizontalClass}
                width={180}
                height={40}
            />
        </span>
    );
}

export default function BrandMark({
    className = '',
    inverted = false,
    usePublicBranding = false,
    logosOnly = false,
    placement = 'header',
    surface = 'product',
    compact = false,
}: BrandMarkProps) {
    const branding = usePage<SharedPageProps>().props.branding as PublicBrandingProps | undefined;
    const websiteName = branding?.website_name?.trim() || 'OurNear Hire';

    if (usePublicBranding && logosOnly) {
        return (
            <PublicSiteLogo
                branding={branding}
                placement={placement}
                compact={compact}
                className={className}
                websiteName={websiteName}
            />
        );
    }

    if (usePublicBranding) {
        const wordmarkClass =
            surface === 'admin'
                ? 'h-7 w-auto max-w-[9.0625rem] shrink-0 object-contain object-left sm:h-8 sm:max-w-[9.5rem]'
                : 'h-7 w-auto max-w-[11rem] shrink-0 object-contain object-left sm:h-8 sm:max-w-[12rem]';
        const wordmarkSrc = surface === 'admin' ? resolvePublicWordmarkUrl(branding) : resolveHireWordmarkUrl(branding);

        return (
            <span className={`inline-flex min-w-0 max-w-full items-center ${className}`}>
                <ConfiguredLogoImage
                    src={wordmarkSrc}
                    alt={websiteName}
                    className={wordmarkClass}
                    width={160}
                    height={32}
                />
            </span>
        );
    }

    const tone = inverted ? 'text-white' : 'text-brand';

    return (
        <span className={`inline-flex items-center gap-2 font-semibold tracking-tight ${className}`} aria-label={websiteName}>
            <span className={tone}>OurNear</span>
            {surface === 'product' ? <span className={inverted ? 'text-white/90' : 'text-ink'}>Hire</span> : null}
        </span>
    );
}
