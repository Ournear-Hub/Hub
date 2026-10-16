import { dividerHorizontal, dividerVertical } from '@/design-system/classNames';

export type DividerOrientation = 'horizontal' | 'vertical';

export type DividerProps = {
    orientation?: DividerOrientation;
    /** When true, separator is visual only (default). When false, exposes separator semantics. */
    decorative?: boolean;
    className?: string;
};

export default function Divider({
    orientation = 'horizontal',
    decorative = true,
    className = '',
}: DividerProps) {
    if (orientation === 'vertical') {
        const verticalClass = `${dividerVertical} ${className}`.trim();
        if (decorative) {
            return <div className={verticalClass} aria-hidden="true" role="presentation" />;
        }
        return <div role="separator" aria-orientation="vertical" className={verticalClass} />;
    }

    const horizontalClass = `${dividerHorizontal} ${className}`.trim();
    if (decorative) {
        return <hr className={horizontalClass} aria-hidden="true" />;
    }
    return <hr className={horizontalClass} />;
}
