import type { IconSize } from '@/icons/types';

/** Standard OurNear icon dimensions (px). */
export const iconSizePx: Record<IconSize, number> = {
    xs: 16,
    sm: 20,
    md: 24,
    lg: 32,
    xl: 40,
};

export function resolveIconPixelSize(size: IconSize | undefined, defaultSize: IconSize): number {
    return iconSizePx[size ?? defaultSize];
}
