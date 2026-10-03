import type { PropsWithChildren } from 'react';
import { cardDensity, type CardDensity } from '@/design-system/classNames';

type CardProps = PropsWithChildren<{
    density?: CardDensity;
    className?: string;
    as?: 'div' | 'section' | 'article';
}>;

export default function Card({ density = 'standard', className = '', as: Tag = 'div', children }: CardProps) {
    return <Tag className={`${cardDensity[density]} ${className}`.trim()}>{children}</Tag>;
}
