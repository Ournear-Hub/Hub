import { Link } from '@inertiajs/react';
import type { ButtonHTMLAttributes, PropsWithChildren, ReactNode } from 'react';
import { focusRing } from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';
import type { IconSize } from '@/icons/types';

export type ButtonSurface = 'public' | 'account' | 'admin' | 'corporate';

type LegacyVariant = 'accent' | 'brand' | 'light';
type CoreVariant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'destructive' | 'link';
export type ButtonVariant = CoreVariant | LegacyVariant | 'danger';

export type ButtonSize = 'sm' | 'md' | 'lg';

export type ButtonProps = PropsWithChildren<
    {
        surface?: ButtonSurface;
        variant?: ButtonVariant;
        size?: ButtonSize;
        loading?: boolean;
        disabled?: boolean;
        fullWidth?: boolean;
        leadingIcon?: string;
        trailingIcon?: string;
        href?: string;
        /** Use a native anchor (full navigation) instead of Inertia — for file downloads. */
        nativeAnchor?: boolean;
        className?: string;
        onClick?: ButtonHTMLAttributes<HTMLButtonElement>['onClick'];
        type?: 'button' | 'submit' | 'reset';
    } & Omit<
        ButtonHTMLAttributes<HTMLButtonElement>,
        'className' | 'children' | 'disabled' | 'onClick' | 'type'
    >
>;

function normalizeVariant(variant: ButtonVariant): CoreVariant {
    switch (variant) {
        case 'accent':
            return 'primary';
        case 'brand':
            return 'secondary';
        case 'light':
            return 'secondary';
        case 'danger':
            return 'destructive';
        default:
            return variant;
    }
}

function isAdminFamily(surface: ButtonSurface): boolean {
    return surface === 'admin' || surface === 'corporate';
}

function variantClasses(surface: ButtonSurface, variant: CoreVariant, legacy?: ButtonVariant): string {
    const admin = isAdminFamily(surface);

    if (variant === 'link') {
        return 'bg-transparent px-0 py-0 text-brand underline-offset-2 hover:underline';
    }

    if (variant === 'primary') {
        return admin
            ? 'bg-brand text-white hover:bg-brand/90 disabled:bg-disabled'
            : 'bg-accent text-white hover:opacity-95 active:opacity-90';
    }

    if (variant === 'secondary') {
        if (legacy === 'light' && !admin) {
            return 'bg-white text-brand hover:bg-surface active:bg-surface';
        }
        return admin
            ? 'border border-line bg-white text-ink hover:bg-surface disabled:text-disabled'
            : 'bg-brand text-white hover:opacity-95 active:opacity-90';
    }

    if (variant === 'outline') {
        return 'border border-line bg-white text-ink hover:bg-surface active:bg-surface';
    }

    if (variant === 'ghost') {
        return admin
            ? 'text-muted hover:bg-surface hover:text-ink disabled:text-disabled'
            : 'bg-transparent text-brand hover:bg-brand/5 active:bg-brand/10';
    }

    if (variant === 'destructive') {
        return admin
            ? 'bg-danger text-white hover:bg-danger/90 disabled:bg-disabled'
            : 'bg-danger text-white hover:opacity-95 active:opacity-90';
    }

    return '';
}

const sizeClasses: Record<ButtonSize, string> = {
    sm: 'min-h-9 px-3 text-xs',
    md: 'min-h-10 px-4 py-2 text-sm',
    lg: 'min-h-11 px-5 py-2.5 text-base',
};

const iconSizeForButton: Record<ButtonSize, IconSize> = {
    sm: 'xs',
    md: 'sm',
    lg: 'md',
};

function ButtonContent({
    loading,
    leadingIcon,
    trailingIcon,
    size,
    children,
}: {
    loading: boolean;
    leadingIcon?: string;
    trailingIcon?: string;
    size: ButtonSize;
    children: ReactNode;
}) {
    const iconSize = iconSizeForButton[size];
    return (
        <>
            {loading ? <OurNearIcon name="spinner" size={iconSize} decorative className="shrink-0" /> : null}
            {!loading && leadingIcon ? (
                <OurNearIcon name={leadingIcon} size={iconSize} decorative className="shrink-0" />
            ) : null}
            {children}
            {trailingIcon ? <OurNearIcon name={trailingIcon} size={iconSize} decorative className="shrink-0" /> : null}
        </>
    );
}

export default function Button({
    surface = 'public',
    variant = 'primary',
    size = 'md',
    loading = false,
    disabled = false,
    fullWidth = false,
    leadingIcon,
    trailingIcon,
    href,
    nativeAnchor = false,
    type = 'button',
    className = '',
    onClick,
    children,
    ...rest
}: ButtonProps) {
    const coreVariant = normalizeVariant(variant);
    const isLinkVariant = coreVariant === 'link';
    const padding = isLinkVariant ? '' : sizeClasses[size];
    const radius = isLinkVariant ? '' : 'rounded-md';
    const classes = [
        'inline-flex items-center justify-center gap-2 font-semibold transition',
        radius,
        padding,
        variantClasses(surface, coreVariant, variant),
        focusRing,
        fullWidth ? 'w-full' : '',
        className,
        disabled || loading ? 'pointer-events-none opacity-60' : '',
    ]
        .filter(Boolean)
        .join(' ');

    const content = (
        <ButtonContent loading={loading} leadingIcon={leadingIcon} trailingIcon={trailingIcon} size={size} children={children} />
    );

    if (href) {
        if (href.startsWith('/') && nativeAnchor) {
            return (
                <a href={href} className={classes} aria-disabled={disabled || loading}>
                    {content}
                </a>
            );
        }

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
        <button
            type={type}
            className={classes}
            onClick={onClick}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            {...rest}
        >
            {content}
        </button>
    );
}
