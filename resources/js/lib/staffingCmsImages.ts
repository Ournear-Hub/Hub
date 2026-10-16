import type { CmsSection } from '@/types/cms';

export type StaffingImageConfig = {
    enabled: boolean;
    desktopUrl: string | null;
    mobileUrl: string | null;
    objectPosition: string;
    mobileObjectPosition: string;
    alt: string;
};

export function staffingSectionImageConfig(section: CmsSection | undefined): StaffingImageConfig {
    const extra = section?.extra ?? {};
    const enabled = extra.image_enabled !== '0';
    const desktopUrl = section?.image_url?.trim() ? section.image_url.trim() : null;
    const mobileFromExtra = typeof extra.mobile_image_url === 'string' ? extra.mobile_image_url.trim() : '';
    const mobileUrl = mobileFromExtra !== '' ? mobileFromExtra : desktopUrl;
    const objectPosition =
        typeof extra.image_object_position === 'string' && extra.image_object_position.trim() !== ''
            ? extra.image_object_position.trim()
            : 'center center';
    const mobileObjectPosition =
        typeof extra.mobile_image_object_position === 'string' &&
        extra.mobile_image_object_position.trim() !== ''
            ? extra.mobile_image_object_position.trim()
            : objectPosition;
    const alt = typeof extra.image_alt === 'string' ? extra.image_alt.trim() : '';

    return {
        enabled,
        desktopUrl,
        mobileUrl,
        objectPosition,
        mobileObjectPosition,
        alt,
    };
}

export function staffingHasDisplayableImage(config: StaffingImageConfig): boolean {
    return config.enabled && Boolean(config.desktopUrl || config.mobileUrl);
}
