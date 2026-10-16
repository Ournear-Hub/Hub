import { focusRing } from '@/design-system/classNames';
import type { ButtonSize, ButtonSurface, ButtonVariant } from '@/Components/atoms/Button';
import type { IconSize } from '@/icons/types';

type CoreVariant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'destructive' | 'link';

export function normalizeButtonVariant(variant: ButtonVariant): CoreVariant {
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

export const buttonSizeClasses: Record<ButtonSize, string> = {
    sm: 'min-h-9 px-3 text-xs',
    md: 'min-h-10 px-4 py-2 text-sm',
    lg: 'min-h-11 px-5 py-2.5 text-base',
};

export const buttonIconSize: Record<ButtonSize, IconSize> = {
    sm: 'xs',
    md: 'sm',
    lg: 'md',
};

export function linkButtonClassName(options: {
    surface: ButtonSurface;
    variant: ButtonVariant;
    size: ButtonSize;
    fullWidth: boolean;
    disabled: boolean;
    className: string;
}): string {
    const { surface, variant, size, fullWidth, disabled, className } = options;
    const coreVariant = normalizeButtonVariant(variant);
    const isLinkVariant = coreVariant === 'link';
    const padding = isLinkVariant ? '' : buttonSizeClasses[size];
    const radius = isLinkVariant ? '' : 'rounded-md';

    return [
        'inline-flex items-center justify-center gap-2 font-semibold transition no-underline',
        radius,
        padding,
        variantClasses(surface, coreVariant, variant),
        focusRing,
        fullWidth ? 'w-full' : '',
        className,
        disabled ? 'pointer-events-none opacity-60' : '',
    ]
        .filter(Boolean)
        .join(' ');
}
