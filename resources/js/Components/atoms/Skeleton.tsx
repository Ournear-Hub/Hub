import type { CSSProperties } from 'react';
import {
    skeletonBase,
    skeletonVariantCircle,
    skeletonVariantRect,
    skeletonVariantText,
} from '@/design-system/classNames';

export type SkeletonVariant = 'text' | 'rect' | 'circle';

export type SkeletonProps = {
    variant?: SkeletonVariant;
    width?: string | number;
    height?: string | number;
    className?: string;
};

const variantClass: Record<SkeletonVariant, string> = {
    text: skeletonVariantText,
    rect: skeletonVariantRect,
    circle: skeletonVariantCircle,
};

function toCssDimension(value: string | number | undefined): string | undefined {
    if (value === undefined) {
        return undefined;
    }
    return typeof value === 'number' ? `${value}px` : value;
}

export default function Skeleton({
    variant = 'text',
    width,
    height,
    className = '',
}: SkeletonProps) {
    const style: CSSProperties = {};

    const cssWidth = toCssDimension(width);
    const cssHeight = toCssDimension(height);

    if (cssWidth !== undefined) {
        style.width = cssWidth;
    }
    if (cssHeight !== undefined) {
        style.height = cssHeight;
    }

    if (variant === 'circle' && cssWidth !== undefined && cssHeight === undefined) {
        style.height = cssWidth;
    }
    if (variant === 'circle' && cssHeight !== undefined && cssWidth === undefined) {
        style.width = cssHeight;
    }

    const rootClass = `${skeletonBase} ${variantClass[variant]} ${className}`.trim();

    return (
        <span
            className={rootClass}
            style={Object.keys(style).length > 0 ? style : undefined}
            aria-hidden="true"
            role="presentation"
            data-skeleton=""
        />
    );
}
