import type { PropsWithChildren } from 'react';
import {
    stackAlignCenter,
    stackAlignEnd,
    stackAlignStart,
    stackAlignStretch,
    stackGapLg,
    stackGapMd,
    stackGapSm,
    stackGapXl,
    stackGapXs,
    stackRoot,
} from '@/design-system/classNames';

/** Vertical gap scale (Tailwind gap-* aligned with spacingScalePx / FieldGroup gaps). */
export type StackGap = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

export type StackAlign = 'stretch' | 'start' | 'center' | 'end';

export type StackProps = PropsWithChildren<{
    gap?: StackGap;
    align?: StackAlign;
    className?: string;
}>;

const gapClass: Record<StackGap, string> = {
    xs: stackGapXs,
    sm: stackGapSm,
    md: stackGapMd,
    lg: stackGapLg,
    xl: stackGapXl,
};

const alignClass: Record<StackAlign, string> = {
    stretch: stackAlignStretch,
    start: stackAlignStart,
    center: stackAlignCenter,
    end: stackAlignEnd,
};

export default function Stack({
    gap = 'md',
    align = 'stretch',
    className = '',
    children,
}: StackProps) {
    const rootClass = `${stackRoot} ${gapClass[gap]} ${alignClass[align]} ${className}`.trim();

    return <div className={rootClass}>{children}</div>;
}
