import type { ReactNode } from 'react';
import {
    chipLabel,
    chipLeadingIcon,
    chipRemoveButton,
    chipRoot,
    chipSizeMd,
    chipSizeSm,
    chipTrailingPaddingDefault,
    chipTrailingPaddingRemovable,
    chipVariantAccent,
    chipVariantBrand,
    chipVariantNeutral,
} from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';

export type ChipVariant = 'neutral' | 'accent' | 'brand';

export type ChipSize = 'sm' | 'md';

type ChipPropsBase = {
    children: ReactNode;
    variant?: ChipVariant;
    size?: ChipSize;
    icon?: ReactNode;
    className?: string;
};

export type ChipProps = ChipPropsBase &
    (
        | { onRemove?: undefined; removeLabel?: undefined }
        | { onRemove: () => void; removeLabel: string }
    );

const variantClass: Record<ChipVariant, string> = {
    neutral: chipVariantNeutral,
    accent: chipVariantAccent,
    brand: chipVariantBrand,
};

const sizeClass: Record<ChipSize, string> = {
    sm: chipSizeSm,
    md: chipSizeMd,
};

export default function Chip({
    children,
    variant = 'neutral',
    size = 'sm',
    icon,
    onRemove,
    removeLabel,
    className = '',
}: ChipProps) {
    const trailingPadding = onRemove ? chipTrailingPaddingRemovable : chipTrailingPaddingDefault;
    const rootClass =
        `${chipRoot} ${sizeClass[size]} ${trailingPadding} ${variantClass[variant]} ${className}`.trim();

    return (
        <span className={rootClass}>
            {icon ? (
                <span className={chipLeadingIcon} aria-hidden="true">
                    {icon}
                </span>
            ) : null}
            <span className={chipLabel}>{children}</span>
            {onRemove ? (
                <button type="button" className={chipRemoveButton} aria-label={removeLabel} onClick={onRemove}>
                    <OurNearIcon name="close" size="xs" decorative />
                </button>
            ) : null}
        </span>
    );
}
