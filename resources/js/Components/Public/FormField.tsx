import type { InputHTMLAttributes, TextareaHTMLAttributes } from 'react';
import { controlBase, controlError, errorText, helperText, labelBase } from '@/design-system/classNames';

type FieldProps = {
    label: string;
    id: string;
    as?: 'input' | 'textarea';
    helper?: string;
    error?: string;
    required?: boolean;
} & InputHTMLAttributes<HTMLInputElement> &
    TextareaHTMLAttributes<HTMLTextAreaElement>;

export default function FormField({
    label,
    id,
    as = 'input',
    helper,
    error,
    required,
    className = '',
    ...props
}: FieldProps) {
    const fieldClass = [controlBase, error ? controlError : '', className].filter(Boolean).join(' ');

    return (
        <div>
            <label htmlFor={id} className={labelBase}>
                {label}
                {required ? (
                    <span className="text-danger" aria-hidden="true">
                        {' '}
                        *
                    </span>
                ) : null}
            </label>
            {as === 'textarea' ? (
                <textarea id={id} className={`${fieldClass} min-h-28`} aria-invalid={error ? true : undefined} {...props} />
            ) : (
                <input id={id} className={fieldClass} aria-invalid={error ? true : undefined} required={required} {...props} />
            )}
            {error ? (
                <p id={`${id}-error`} className={errorText} role="alert">
                    {error}
                </p>
            ) : helper ? (
                <p id={`${id}-helper`} className={helperText}>
                    {helper}
                </p>
            ) : null}
        </div>
    );
}

/** Shared select styling for public/account forms (use on native &lt;select&gt;). */
export const selectControlClass = controlBase;
