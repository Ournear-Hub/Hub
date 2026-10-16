import type { PropsWithChildren } from 'react';
import {
    containerGutterLg,
    containerGutterMd,
    containerGutterSm,
    containerRoot,
    containerSize2xl,
    containerSizeFull,
    containerSizeLg,
    containerSizeMd,
    containerSizeSm,
    containerSizeXl,
} from '@/design-system/classNames';

/** Content max-width scale (Tailwind max-w-*). */
export type ContainerSize = 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full';

/** Horizontal page gutters — `none` for shells that already pad (e.g. admin main). */
export type ContainerGutter = 'none' | 'sm' | 'md' | 'lg';

export type ContainerProps = PropsWithChildren<{
    size?: ContainerSize;
    gutter?: ContainerGutter;
    className?: string;
}>;

const sizeClass: Record<ContainerSize, string> = {
    sm: containerSizeSm,
    md: containerSizeMd,
    lg: containerSizeLg,
    xl: containerSizeXl,
    '2xl': containerSize2xl,
    full: containerSizeFull,
};

const gutterClass: Record<ContainerGutter, string> = {
    none: '',
    sm: containerGutterSm,
    md: containerGutterMd,
    lg: containerGutterLg,
};

export default function Container({
    size = 'md',
    gutter = 'md',
    className = '',
    children,
}: ContainerProps) {
    const rootClass = `${containerRoot} ${sizeClass[size]} ${gutterClass[gutter]} ${className}`.trim();

    return <div className={rootClass}>{children}</div>;
}
