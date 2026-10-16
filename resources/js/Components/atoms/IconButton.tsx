import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { focusRing } from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';
import type { ButtonSurface } from '@/Components/atoms/Button';
import type { IconSize } from '@/icons/types';

export type IconButtonVariant = 'default' | 'primary' | 'secondary' | 'ghost' | 'destructive' | 'outline';

export type IconButtonSize = 'sm' | 'md' | 'lg';

export type IconButtonProps = {
    icon?: string;
    label: string;
    surface?: ButtonSurface;
    variant?: IconButtonVariant;
    size?: IconButtonSize;
    href?: string;
    onClick?: () => void;
    type?: 'button' | 'submit';
    disabled?: boolean;
    loading?: boolean;
    className?: string;
    /** @deprecated Prefer `icon` prop — legacy V2 child icon */
    children?: ReactNode;
    'aria-expanded'?: boolean;
    'aria-controls'?: string;
    'aria-pressed'?: boolean;
};

const boxSize: Record<IconButtonSize, string> = {
    sm: 'h-9 w-9',
    md: 'h-10 w-10',
    lg: 'h-11 w-11',
};

const iconSize: Record<IconButtonSize, IconSize> = {
    sm: 'xs',
    md: 'sm',
    lg: 'md',
};

function isAdminFamily(surface: ButtonSurface): boolean {
    return surface === 'admin' || surface === 'corporate';
}

function variantClasses(surface: ButtonSurface, variant: IconButtonVariant): string {
    const admin = isAdminFamily(surface);

    switch (variant) {
        case 'primary':
            return admin
                ? 'border-transparent bg-brand text-white hover:bg-brand/90'
                : 'border-transparent bg-accent text-white hover:opacity-95';
        case 'secondary':
        case 'outline':
            return 'border border-line bg-white text-ink hover:bg-surface';
        case 'ghost':
            return admin
                ? 'border-transparent bg-transparent text-muted hover:bg-surface hover:text-ink'
                : 'border-transparent bg-transparent text-brand hover:bg-brand/5';
        case 'destructive':
            return 'border-transparent bg-danger text-white hover:bg-danger/90';
        case 'default':
        default:
            return 'border border-line bg-white text-ink hover:bg-surface';
    }
}

export default function IconButton({
    icon,
    label,
    surface = 'public',
    variant = 'default',
    size = 'md',
    href,
    onClick,
    type = 'button',
    disabled = false,
    loading = false,
    className = '',
    children,
    'aria-expanded': ariaExpanded,
    'aria-controls': ariaControls,
    'aria-pressed': ariaPressed,
}: IconButtonProps) {
    if (!label.trim()) {
        if (import.meta.env.DEV) {
            console.warn('[IconButton] Accessible label is required.');
        }
    }

    const classes = [
        'inline-flex items-center justify-center rounded-md transition',
        boxSize[size],
        variantClasses(surface, variant),
        focusRing,
        disabled || loading ? 'pointer-events-none opacity-60' : '',
        className,
    ]
        .filter(Boolean)
        .join(' ');

    const iconContent = loading ? (
        <OurNearIcon name="spinner" size={iconSize[size]} decorative />
    ) : icon ? (
        <OurNearIcon name={icon} size={iconSize[size]} decorative />
    ) : (
        children
    );

    if (href) {
        if (href.startsWith('/')) {
            return (
                <Link href={href} className={classes} aria-label={label} aria-disabled={disabled || loading}>
                    {iconContent}
                </Link>
            );
        }
        return (
            <a href={href} className={classes} aria-label={label} aria-disabled={disabled || loading}>
                {iconContent}
            </a>
        );
    }

    return (
        <button
            type={type}
            className={classes}
            aria-label={label}
            aria-busy={loading || undefined}
            aria-expanded={ariaExpanded}
            aria-controls={ariaControls}
            aria-pressed={ariaPressed}
            onClick={onClick}
            disabled={disabled || loading}
        >
            {iconContent}
        </button>
    );
}
