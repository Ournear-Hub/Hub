import type { PropsWithChildren } from 'react';
import {
    aspectRatioPortrait,
    aspectRatioRoot,
    aspectRatioSquare,
    aspectRatioVideo,
} from '@/design-system/classNames';

/** Preset ratios — evidence-based; no arbitrary ratio prop in PR-4V. */
export type AspectRatioPreset = 'square' | 'video' | 'portrait';

export type AspectRatioProps = PropsWithChildren<{
    ratio: AspectRatioPreset;
    className?: string;
}>;

const ratioClass: Record<AspectRatioPreset, string> = {
    square: aspectRatioSquare,
    video: aspectRatioVideo,
    portrait: aspectRatioPortrait,
};

export default function AspectRatio({ ratio, className = '', children }: AspectRatioProps) {
    const rootClass = `${aspectRatioRoot} ${ratioClass[ratio]} ${className}`.trim();

    return <div className={rootClass}>{children}</div>;
}
