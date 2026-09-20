import type { ButtonHTMLAttributes, ReactNode } from 'react';

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
    children: ReactNode;
};

const variants: Record<NonNullable<ButtonProps['variant']>, string> = {
    primary: 'bg-brand text-white hover:bg-brand/90 disabled:bg-disabled',
    secondary: 'border border-line bg-white text-ink hover:bg-surface disabled:text-disabled',
    ghost: 'text-muted hover:bg-surface hover:text-ink disabled:text-disabled',
    danger: 'bg-danger text-white hover:bg-danger/90 disabled:bg-disabled',
};

export default function Button({
    variant = 'primary',
    className = '',
    type = 'button',
    children,
    ...props
}: ButtonProps) {
    return (
        <button
            type={type}
            className={`inline-flex min-h-10 items-center justify-center rounded-md px-4 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 ${variants[variant]} ${className}`}
            {...props}
        >
            {children}
        </button>
    );
}
