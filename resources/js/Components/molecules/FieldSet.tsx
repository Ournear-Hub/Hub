import { errorText, helperText, labelBase } from '@/design-system/classNames';
import { useId, type ReactNode } from 'react';

export type FieldSetProps = {
    /** Accessible group name — rendered as native legend. */
    legend: ReactNode;
    /** Section context for the group — not a substitute for individual field hints. */
    description?: ReactNode;
    /**
     * Group-level constraint message only (e.g. “Complete this section”).
     * Does not set aria-invalid on children.
     */
    error?: ReactNode;
    /** Visual required indicator on the legend — does not mark each child required. */
    required?: boolean;
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

export default function FieldSet({
    legend,
    description,
    error,
    required = false,
    className = '',
    children,
}: FieldSetProps) {
    const reactId = useId();
    const descriptionId = `${reactId}-description`;
    const errorId = `${reactId}-error`;
    const hasError = hasContent(error);
    const showDescription = !hasError && hasContent(description);

    const describedByParts: string[] = [];
    if (showDescription) {
        describedByParts.push(descriptionId);
    }
    if (hasError) {
        describedByParts.push(errorId);
    }
    const describedBy = describedByParts.length > 0 ? describedByParts.join(' ') : undefined;

    const rootClass = `min-w-0 border-0 p-0 ${className}`.trim();

    return (
        <fieldset className={rootClass} aria-describedby={describedBy}>
            <legend className={`${labelBase} mb-2 block w-full`.trim()}>
                <LegendContent required={required}>{legend}</LegendContent>
            </legend>
            {showDescription ? (
                <p id={descriptionId} className={`${helperText} mb-4`.trim()}>
                    {description}
                </p>
            ) : null}
            {children}
            {hasError ? (
                <p id={errorId} className={`${errorText} mt-4`.trim()} role="alert">
                    {error}
                </p>
            ) : null}
        </fieldset>
    );
}
