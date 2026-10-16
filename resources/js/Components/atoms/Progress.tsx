import {
    progressFill,
    progressHeader,
    progressLabelText,
    progressRoot,
    progressTrackLg,
    progressTrackMd,
    progressTrackSm,
    progressValueText,
} from '@/design-system/classNames';

export type ProgressSize = 'sm' | 'md' | 'lg';

export type ProgressProps = {
    value: number;
    max?: number;
    label?: string;
    showValue?: boolean;
    size?: ProgressSize;
    className?: string;
};

const trackClass: Record<ProgressSize, string> = {
    sm: progressTrackSm,
    md: progressTrackMd,
    lg: progressTrackLg,
};

function clamp(value: number, min: number, max: number): number {
    return Math.min(max, Math.max(min, value));
}

export default function Progress({
    value,
    max = 100,
    label,
    showValue = false,
    size = 'md',
    className = '',
}: ProgressProps) {
    const safeMax = max > 0 ? max : 100;
    const clampedValue = clamp(value, 0, safeMax);
    const percent = Math.round((clampedValue / safeMax) * 100);
    const hasHeader = Boolean(label || showValue);
    const rootClass = `${progressRoot} ${className}`.trim();

    const progressbarLabel = label ?? (showValue ? `${percent}% complete` : 'Progress');

    return (
        <div className={rootClass}>
            {hasHeader ? (
                <div className={progressHeader}>
                    {label ? <span className={progressLabelText}>{label}</span> : <span />}
                    {showValue ? <span className={progressValueText}>{percent}%</span> : null}
                </div>
            ) : null}
            <div
                className={trackClass[size]}
                role="progressbar"
                aria-valuemin={0}
                aria-valuemax={safeMax}
                aria-valuenow={clampedValue}
                aria-label={progressbarLabel}
            >
                <div className={progressFill} style={{ width: `${percent}%` }} />
            </div>
        </div>
    );
}
