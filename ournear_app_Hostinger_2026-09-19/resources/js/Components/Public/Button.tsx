import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

type ButtonProps = PropsWithChildren<{
    href?: string;
    type?: 'button' | 'submit';
    variant?: 'accent' | 'brand' | 'light' | 'outline';
    className?: string;
    onClick?: () => void;
}>;

const variants: Record<NonNullable<ButtonProps['variant']>, string> = {
    accent: 'bg-accent text-white hover:opacity-95',
    brand: 'bg-brand text-white hover:opacity-95',
    light: 'bg-white text-brand hover:bg-surface',
    outline: 'border border-line bg-white text-ink hover:bg-surface',
};

export default function Button({
    href,
    type = 'button',
    variant = 'accent',
    className = '',
    onClick,
    children,
}: ButtonProps) {
    const classes = `inline-flex items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold transition ${variants[variant]} ${className}`;

    if (href) {
        if (href.startsWith('/')) {
            return (
                <Link href={href} className={classes}>
                    {children}
                </Link>
            );
        }

        return (
            <a href={href} className={classes}>
                {children}
            </a>
        );
    }

    return (
        <button type={type} className={classes} onClick={onClick}>
            {children}
        </button>
    );
}
