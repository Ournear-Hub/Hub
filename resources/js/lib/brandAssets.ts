/** Bundled official brand files under /public/brand (PNG preferred; JPEG fallback). */
export const brandAssetPaths = {
    ournearSymbol: '/brand/ournear-symbol.png',
    ournearSymbolFallback: '/brand/ournear-symbol.jpeg',
    ournearWordmark: '/brand/ournear-wordmark.png',
    ournearWordmarkFallback: '/brand/ournear-wordmark.jpeg',
    ournearHireWordmark: '/brand/ournear-hire-wordmark.png',
    ournearHireWordmarkFallback: '/brand/ournear-hire-wordmark.png',
    authSplashVideo: '/brand/ournear-splash.mp4',
    authSplashPoster: '/brand/ournear-symbol.jpeg',
} as const;

export type PublicBrandingProps = {
    website_name: string;
    brand_mark?: string;
    logo_url: string | null;
    header_logo_url?: string | null;
    footer_logo_url?: string | null;
    wordmark_url?: string | null;
    icon_url: string | null;
    favicon_url: string | null;
    hire_wordmark_url?: string | null;
    auth_symbol_url?: string | null;
    auth_wordmark_url?: string | null;
    auth_splash_video_url?: string | null;
    auth_splash_poster_url?: string | null;
};

export function resolveHireWordmarkUrl(branding?: PublicBrandingProps | null): string {
    return branding?.hire_wordmark_url ?? branding?.wordmark_url ?? brandAssetPaths.ournearHireWordmarkFallback;
}

export function resolvePublicSymbolUrl(branding?: PublicBrandingProps | null): string {
    return (
        branding?.auth_symbol_url ??
        branding?.icon_url ??
        branding?.logo_url ??
        brandAssetPaths.ournearSymbolFallback
    );
}

export function resolvePublicWordmarkUrl(branding?: PublicBrandingProps | null): string {
    return (
        branding?.auth_wordmark_url ??
        branding?.hire_wordmark_url ??
        branding?.wordmark_url ??
        branding?.logo_url ??
        brandAssetPaths.ournearHireWordmarkFallback
    );
}

export function resolveAuthSymbolUrl(branding?: PublicBrandingProps | null): string {
    return resolvePublicSymbolUrl(branding);
}

export function resolveAuthWordmarkUrl(branding?: PublicBrandingProps | null): string {
    return resolveHireWordmarkUrl(branding);
}

export function resolveAuthSplashVideoUrl(branding?: PublicBrandingProps | null): string | null {
    return branding?.auth_splash_video_url ?? brandAssetPaths.authSplashVideo;
}

export function resolveAuthSplashPosterUrl(branding?: PublicBrandingProps | null): string {
    return branding?.auth_splash_poster_url ?? brandAssetPaths.authSplashPoster;
}
