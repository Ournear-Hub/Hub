import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { SpinnerIcon } from '@/Components/UI/icons';
import { focusRing } from '@/design-system/classNames';

type LegacyVariant = 'accent' | 'brand' | 'light' | 'outline';
type ExtendedVariant = LegacyVariant | 'primary' | 'secondary' | 'ghost' | 'destructive' | 'link';

type ButtonProps = PropsWithChildren<{
    href?: string;
    type?: 'button' | 'submit';
    /** @deprecated Use `primary` — kept for compatibility */
    variant?: ExtendedVariant;
    className?: string;
    onClick?: () => void;
    disabled?: boolean;
    loading?: boolean;
}>;

const variantClasses: Record<ExtendedVariant, string> = {
    primary: 'bg-accent text-white hover:opacity-95 active:opacity-90',
    accent: 'bg-accent text-white hover:opacity-95 active:opacity-90',
    secondary: 'bg-brand text-white hover:opacity-95 active:opacity-90',
    brand: 'bg-brand text-white hover:opacity-95 active:opacity-90',
    light: 'bg-white text-brand hover:bg-surface active:bg-surface',
    outline: 'border border-line bg-white text-ink hover:bg-surface active:bg-surface',
    ghost: 'bg-transparent text-brand hover:bg-brand/5 active:bg-brand/10',
    destructive: 'bg-danger text-white hover:opacity-95 active:opacity-90',
    link: 'bg-transparent px-0 py-0 text-brand underline-offset-2 hover:underline',
};

function normalizeVariant(variant: ExtendedVariant): ExtendedVariant {
    if (variant === 'accent') {
        return 'primary';
    }
    if (variant === 'brand') {
        return 'secondary';
    }
    return variant;
}

export default function Button({
    href,
    type = 'button',
    variant = 'accent',
    className = '',
    onClick,
    disabled = false,
    loading = false,
    children,
}: ButtonProps) {
    const resolved = normalizeVariant(variant);
    const isLinkVariant = resolved === 'link';
    const padding = isLinkVariant ? '' : 'px-4 py-2.5';
    const radius = isLinkVariant ? '' : 'rounded-md';
    const classes = [
        'inline-flex items-center justify-center gap-2 text-sm font-semibold transition',
        radius,
        padding,
        variantClasses[variant] ?? variantClasses[resolved],
        focusRing,
        className,
        disabled || loading ? 'pointer-events-none opacity-60' : '',
    ]
        .filter(Boolean)
        .join(' ');

    const content = (
        <>
            {loading ? <SpinnerIcon className="h-4 w-4 shrink-0 animate-spin" /> : null}
            {children}
        </>
    );

    if (href) {
        if (href.startsWith('/')) {
            return (
                <Link href={href} className={classes} aria-disabled={disabled || loading}>
                    {content}
                </Link>
            );
        }

        return (
            <a href={href} className={classes} aria-disabled={disabled || loading}>
                {content}
            </a>
        );
    }

    return (
        <button type={type} className={classes} onClick={onClick} disabled={disabled || loading} aria-busy={loading}>
            {content}
        </button>
    );
}
