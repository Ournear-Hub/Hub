import type { AnchorHTMLAttributes, PropsWithChildren } from 'react';
import {
    linkBase,
    linkUnderlineAlways,
    linkUnderlineHover,
    linkUnderlineNone,
    linkVariantDefault,
    linkVariantMuted,
    linkVariantSubtle,
} from '@/design-system/classNames';

export type LinkVariant = 'default' | 'muted' | 'subtle';
export type LinkUnderline = 'none' | 'hover' | 'always';

export type LinkProps = PropsWithChildren<
    {
        href: string;
        variant?: LinkVariant;
        underline?: LinkUnderline;
        className?: string;
    } & Omit<AnchorHTMLAttributes<HTMLAnchorElement>, 'href' | 'className' | 'children'>
>;

const variantClass: Record<LinkVariant, string> = {
    default: linkVariantDefault,
    muted: linkVariantMuted,
    subtle: linkVariantSubtle,
};

const underlineClass: Record<LinkUnderline, string> = {
    none: linkUnderlineNone,
    hover: linkUnderlineHover,
    always: linkUnderlineAlways,
};

export default function Link({
    href,
    variant = 'default',
    underline = 'hover',
    className = '',
    children,
    ...rest
}: LinkProps) {
    const classes =
        `${linkBase} ${variantClass[variant]} ${underlineClass[underline]} ${className}`.trim();

    return (
        <a href={href} className={classes} {...rest}>
            {children}
        </a>
    );
}
