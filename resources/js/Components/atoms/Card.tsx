import type { PropsWithChildren } from 'react';
import { cardDensity, type CardDensity } from '@/design-system/classNames';

export type CardPadding = CardDensity | 'none';

export type CardProps = PropsWithChildren<{
    /**
     * Structural padding/elevation preset from `cardDensity` recipes.
     * `none` — border surface only (caller-owned inner spacing).
     */
    padding?: CardPadding;
    className?: string;
    as?: 'div' | 'section' | 'article';
}>;

const paddingClass: Record<CardPadding, string> = {
    none: 'rounded-xl border border-line bg-white',
    compact: cardDensity.compact,
    standard: cardDensity.standard,
    feature: cardDensity.feature,
};

export default function Card({ padding = 'standard', className = '', as: Tag = 'div', children }: CardProps) {
    const rootClass = `${paddingClass[padding]} ${className}`.trim();

    return <Tag className={rootClass}>{children}</Tag>;
}
