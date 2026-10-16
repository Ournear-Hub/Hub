import type { PropsWithChildren } from 'react';
import Badge from '@/Components/atoms/Badge';
import type { BadgeVariant } from '@/Components/atoms/Badge';

export type StatusBadgeProps = PropsWithChildren<{
    /** Semantic presentation tone (UI / public surfaces). */
    tone?: BadgeVariant;
    /** Alias for `tone` — matches canonical Badge API. */
    variant?: BadgeVariant;
    /** Domain status map classes (account/admin adapters); overrides tone/variant when set. */
    toneClassName?: string;
    className?: string;
}>;

/**
 * Tone-based status presentation. Domain-specific status strings belong in
 * surface adapters (Admin/ui, Account) that map labels to presentation classes.
 */
export default function StatusBadge({ tone, variant, toneClassName, className = '', children }: StatusBadgeProps) {
    if (toneClassName) {
        return (
            <Badge toneClassName={toneClassName} className={className}>
                {children}
            </Badge>
        );
    }

    const resolved = tone ?? variant ?? 'neutral';

    return (
        <Badge variant={resolved} className={className}>
            {children}
        </Badge>
    );
}
