import { errorText, helperText, labelBase } from '@/design-system/classNames';
import { useId, type ReactNode } from 'react';

export type FieldGroupOrientation = 'vertical' | 'horizontal';

export type FieldGroupGap = 'sm' | 'md' | 'lg';

export type FieldGroupProps = {
    /** Group legend when the children are related form controls. */
    label?: ReactNode;
    hint?: ReactNode;
    /** Group-level message only — does not set aria-invalid on children. */
    error?: ReactNode;
    /** Visual required indicator on the group legend/label — does not mark each child required. */
    required?: boolean;
    orientation?: FieldGroupOrientation;
    gap?: FieldGroupGap;
    className?: string;
    children: ReactNode;
};

function hasContent(node: ReactNode): boolean {
    if (node === null || node === undefined || node === false) {
        return false;
    }
    if (typeof node === 'string') {
        return node.trim().length > 0;
    }
    return true;
}

const gapClass: Record<FieldGroupGap, string> = {
    sm: 'gap-2',
    md: 'gap-4',
    lg: 'gap-6',
};

function layoutClass(orientation: FieldGroupOrientation): string {
    if (orientation === 'horizontal') {
        return 'flex flex-col sm:flex-row sm:flex-wrap sm:items-end';
    }
    return 'flex flex-col';
}

function LegendContent({ required, children }: { required: boolean; children: ReactNode }) {
    return (
        <>
            {children}
            {required ? (
                <>
                    <span className="text-danger" aria-hidden="true">
                        {' '}
                        *
                    </span>
                    <span className="sr-only"> (required)</span>
                </>
            ) : null}
        </>
    );
}

export default function FieldGroup({
    label,
    hint,
    error,
    required = false,
    orientation = 'vertical',
    gap = 'md',
    className = '',
    children,
}: FieldGroupProps) {
    const reactId = useId();
    const hintId = `${reactId}-hint`;
    const errorId = `${reactId}-error`;
    const hasError = hasContent(error);
    const showHint = !hasError && hasContent(hint);

    const describedByParts: string[] = [];
    if (showHint) {
        describedByParts.push(hintId);
    }
    if (hasError) {
        describedByParts.push(errorId);
    }
    const describedBy = describedByParts.length > 0 ? describedByParts.join(' ') : undefined;

    const body = (
        <>
            <div className={`${layoutClass(orientation)} ${gapClass[gap]}`.trim()}>{children}</div>
            {hasError ? (
                <p id={errorId} className={errorText} role="alert">
                    {error}
                </p>
            ) : showHint ? (
                <p id={hintId} className={helperText}>
                    {hint}
                </p>
            ) : null}
        </>
    );

    const rootClass = className.trim();

    if (hasContent(label)) {
        return (
            <fieldset
                className={`min-w-0 border-0 p-0 ${rootClass}`.trim()}
                aria-describedby={describedBy}
            >
                <legend className={`${labelBase} mb-2 block w-full`.trim()}>
                    <LegendContent required={required}>{label}</LegendContent>
                </legend>
                {body}
            </fieldset>
        );
    }

    return (
        <div className={rootClass || undefined} aria-describedby={describedBy}>
            {body}
        </div>
    );
}
