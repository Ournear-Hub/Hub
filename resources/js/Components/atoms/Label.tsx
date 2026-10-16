import type { LabelHTMLAttributes, PropsWithChildren } from 'react';
import { labelBase } from '@/design-system/classNames';

export type LabelProps = PropsWithChildren<
    {
        htmlFor?: string;
        required?: boolean;
        className?: string;
    } & Omit<LabelHTMLAttributes<HTMLLabelElement>, 'className' | 'htmlFor' | 'children'>
>;

export default function Label({ htmlFor, required = false, className = '', children, ...rest }: LabelProps) {
    return (
        <label htmlFor={htmlFor} className={`${labelBase} ${className}`.trim()} {...rest}>
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
        </label>
    );
}
