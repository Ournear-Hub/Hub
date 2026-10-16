import CanonicalIconButton, { type IconButtonProps as CanonicalIconButtonProps } from '@/Components/atoms/IconButton';
import type { ReactNode } from 'react';

type LegacyIconButtonProps = Omit<CanonicalIconButtonProps, 'icon'> & {
    children: ReactNode;
};

/**
 * V2 compatibility alias — account/public icon actions.
 * Canonical implementation: `@/Components/atoms/IconButton`.
 */
export default function IconButton(props: LegacyIconButtonProps) {
    return <CanonicalIconButton surface="public" {...props} />;
}
