import { createElement, type PropsWithChildren } from 'react';
import { typography } from '@/design-system/classNames';

export type HeadingLevel = 1 | 2 | 3 | 4 | 5 | 6;

export type HeadingProps = PropsWithChildren<{
    level: HeadingLevel;
    className?: string;
}>;

const levelClasses: Record<HeadingLevel, string> = {
    1: typography.pageTitle,
    2: typography.sectionTitle,
    3: typography.cardTitle,
    4: `${typography.body} font-semibold`,
    5: `${typography.secondary} font-semibold text-ink`,
    6: `${typography.caption} font-semibold text-ink`,
};

export default function Heading({ level, className = '', children }: HeadingProps) {
    return createElement(`h${level}`, {
        className: `${levelClasses[level]} ${className}`.trim(),
        children,
    });
}
