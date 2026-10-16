import type { PropsWithChildren } from 'react';
import {
    clusterAlignBaseline,
    clusterAlignCenter,
    clusterAlignEnd,
    clusterAlignStart,
    clusterAlignStretch,
    clusterGapLg,
    clusterGapMd,
    clusterGapSm,
    clusterGapXl,
    clusterGapXs,
    clusterNoWrap,
    clusterRoot,
    clusterWrap,
} from '@/design-system/classNames';

/** Horizontal gap scale — same Tailwind gap-* mapping as Stack (see stackGap* / spacingScalePx). */
export type ClusterGap = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

export type ClusterAlign = 'stretch' | 'start' | 'center' | 'end' | 'baseline';

export type ClusterProps = PropsWithChildren<{
    gap?: ClusterGap;
    align?: ClusterAlign;
    wrap?: boolean;
    className?: string;
}>;

const gapClass: Record<ClusterGap, string> = {
    xs: clusterGapXs,
    sm: clusterGapSm,
    md: clusterGapMd,
    lg: clusterGapLg,
    xl: clusterGapXl,
};

const alignClass: Record<ClusterAlign, string> = {
    stretch: clusterAlignStretch,
    start: clusterAlignStart,
    center: clusterAlignCenter,
    end: clusterAlignEnd,
    baseline: clusterAlignBaseline,
};

export default function Cluster({
    gap = 'sm',
    align = 'center',
    wrap = false,
    className = '',
    children,
}: ClusterProps) {
    const rootClass = `${clusterRoot} ${wrap ? clusterWrap : clusterNoWrap} ${gapClass[gap]} ${alignClass[align]} ${className}`.trim();

    return <div className={rootClass}>{children}</div>;
}
