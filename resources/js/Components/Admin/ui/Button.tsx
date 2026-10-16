import CanonicalButton from '@/Components/atoms/Button';
import type { ButtonHTMLAttributes, ReactNode } from 'react';

type AdminButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
    children: ReactNode;
};

/**
 * V2 compatibility alias — Admin / corporate surfaces (primary = brand #214449).
 * Canonical implementation: `@/Components/atoms/Button`.
 */
export default function Button({
    variant = 'primary',
    className = '',
    type = 'button',
    children,
    ...props
}: AdminButtonProps) {
    const mappedVariant = variant === 'danger' ? 'destructive' : variant;

    return (
        <CanonicalButton surface="admin" variant={mappedVariant} type={type} className={className} {...props}>
            {children}
        </CanonicalButton>
    );
}
