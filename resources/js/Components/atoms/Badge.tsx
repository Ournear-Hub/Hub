import type { PropsWithChildren } from 'react';
import { statusTone, type StatusTone } from '@/design-system/classNames';

/** Semantic presentation variants (domain-neutral). */
export type BadgeVariant = StatusTone;

export type BadgeSize = 'sm' | 'md';

export type BadgeWeight = 'medium' | 'semibold';

export type BadgeProps = PropsWithChildren<{
    /** Semantic tone from the design system token map. */
    variant?: BadgeVariant;
    /**
     * Raw Tailwind classes for background/text when presentation is driven by a
     * domain status map (admin/account adapters). Takes precedence over `variant`.
     */
    toneClassName?: string;
    size?: BadgeSize;
    weight?: BadgeWeight;
    className?: string;
}>;

const sizeClasses: Record<BadgeSize, string> = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-0.5 text-xs',
};

const weightClasses: Record<BadgeWeight, string> = {
    medium: 'font-medium',
    semibold: 'font-semibold',
};

export default function Badge({
    variant = 'neutral',
    toneClassName,
    size = 'md',
    weight = 'semibold',
    className = '',
    children,
}: BadgeProps) {
    const colorClasses = toneClassName ?? statusTone[variant];

    return (
        <span
            className={`inline-flex items-center rounded-full ${sizeClasses[size]} ${weightClasses[weight]} ${colorClasses} ${className}`.trim()}
        >
            {children}
        </span>
    );
}
