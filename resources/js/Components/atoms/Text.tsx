import type { ElementType, PropsWithChildren } from 'react';
import { typography } from '@/design-system/classNames';

export type TextVariant = 'body' | 'secondary' | 'caption' | 'eyebrow';

export type TextTone = 'default' | 'muted' | 'subtle' | 'disabled' | 'danger' | 'success' | 'warning';

export type TextAs = 'p' | 'span' | 'div';

export type TextProps = PropsWithChildren<{
    as?: TextAs;
    variant?: TextVariant;
    tone?: TextTone;
    className?: string;
}>;

const toneClasses: Record<TextTone, string> = {
    default: '',
    muted: 'text-muted',
    subtle: 'text-muted',
    disabled: 'text-disabled',
    danger: 'text-danger',
    success: 'text-success',
    warning: 'text-warning',
};

export default function Text({ as = 'p', variant = 'body', tone = 'default', className = '', children }: TextProps) {
    const Component = as as ElementType;
    const variantClass = typography[variant];
    const toneClass = toneClasses[tone];

    return <Component className={`${variantClass} ${toneClass} ${className}`.trim()}>{children}</Component>;
}
