import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { focusRing } from '@/design-system/classNames';

type Props = {
    href?: string;
    label: string;
    onClick?: () => void;
    type?: 'button' | 'submit';
    disabled?: boolean;
    className?: string;
    children: ReactNode;
    'aria-expanded'?: boolean;
    'aria-controls'?: string;
};

const base = `inline-flex h-10 w-10 items-center justify-center rounded-md border border-line bg-white text-ink transition hover:bg-surface ${focusRing}`;

export default function IconButton({
    href,
    label,
    onClick,
    type = 'button',
    disabled,
    className = '',
    children,
    'aria-expanded': ariaExpanded,
    'aria-controls': ariaControls,
}: Props) {
    const classes = `${base} ${disabled ? 'pointer-events-none opacity-60' : ''} ${className}`.trim();

    if (href) {
        if (href.startsWith('/')) {
            return (
                <Link href={href} className={classes} aria-label={label}>
                    {children}
                </Link>
            );
        }
        return (
            <a href={href} className={classes} aria-label={label}>
                {children}
            </a>
        );
    }

    return (
        <button
            type={type}
            className={classes}
            aria-label={label}
            aria-expanded={ariaExpanded}
            aria-controls={ariaControls}
            onClick={onClick}
            disabled={disabled}
        >
            {children}
        </button>
    );
}
