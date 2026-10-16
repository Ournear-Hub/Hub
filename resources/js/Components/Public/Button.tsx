import CanonicalButton, { type ButtonProps as CanonicalButtonProps } from '@/Components/atoms/Button';
import type { PropsWithChildren } from 'react';

type LegacyVariant = 'accent' | 'brand' | 'light' | 'outline';
type ExtendedVariant = LegacyVariant | CanonicalButtonProps['variant'];

type PublicButtonProps = PropsWithChildren<
    Omit<CanonicalButtonProps, 'surface' | 'variant'> & {
        /** @deprecated Use `primary` — kept for compatibility */
        variant?: ExtendedVariant;
    }
>;

/**
 * V2 compatibility alias — Public / marketing surfaces (primary = accent #F15A24).
 * Canonical implementation: `@/Components/atoms/Button`.
 */
export default function Button({ variant = 'accent', ...props }: PublicButtonProps) {
    return <CanonicalButton surface="public" variant={variant} {...props} />;
}
