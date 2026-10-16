import { spinnerIcon, spinnerRoot } from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';
import type { IconSize } from '@/icons/types';

export type SpinnerSize = 'sm' | 'md' | 'lg';

export type SpinnerProps = {
    size?: SpinnerSize;
    /** Accessible name (defaults to “Loading”). */
    label?: string;
    className?: string;
};

const iconSizeForSpinner: Record<SpinnerSize, IconSize> = {
    sm: 'xs',
    md: 'sm',
    lg: 'md',
};

export default function Spinner({ size = 'md', label, className = '' }: SpinnerProps) {
    const accessibleLabel = label ?? 'Loading';
    const rootClass = `${spinnerRoot} ${className}`.trim();

    return (
        <span className={rootClass} role="status" aria-label={accessibleLabel}>
            <OurNearIcon
                name="spinner"
                size={iconSizeForSpinner[size]}
                decorative
                className={spinnerIcon}
            />
        </span>
    );
}
