import { useRef, type InputHTMLAttributes, type MouseEvent, type TextareaHTMLAttributes } from 'react';
import {
    authControlBase,
    authControlError,
    authErrorText,
    authHelperText,
    authLabelBase,
    controlBase,
    controlError,
    errorText,
    helperText,
    labelBase,
} from '@/design-system/classNames';

type FieldProps = {
    label: string;
    id: string;
    as?: 'input' | 'textarea';
    helper?: string;
    error?: string;
    required?: boolean;
    appearance?: 'default' | 'auth';
} & InputHTMLAttributes<HTMLInputElement> &
    TextareaHTMLAttributes<HTMLTextAreaElement>;

export default function FormField({
    label,
    id,
    as = 'input',
    helper,
    error,
    required,
    appearance = 'default',
    className = '',
    ...props
}: FieldProps) {
    const isAuth = appearance === 'auth';
    const base = isAuth ? authControlBase : controlBase;
    const err = isAuth ? authControlError : controlError;
    const fieldClass = [base, error ? err : '', className].filter(Boolean).join(' ');
    const inputRef = useRef<HTMLInputElement>(null);
    const isDateInput = as === 'input' && props.type === 'date';

    const openDatePicker = () => {
        const el = inputRef.current;
        if (!el || props.disabled) {
            return;
        }
        el.focus();
        if (typeof el.showPicker === 'function') {
            try {
                el.showPicker();
            } catch {
                // Some browsers block showPicker outside a direct user gesture.
            }
        }
    };

    const onDateFieldClick = (event: MouseEvent<HTMLDivElement>) => {
        if (!isDateInput) {
            return;
        }
        if (event.target instanceof HTMLInputElement) {
            openDatePicker();
            return;
        }
        openDatePicker();
    };

    return (
        <div onClick={isDateInput ? onDateFieldClick : undefined} className={isDateInput ? 'cursor-text' : undefined}>
            <label htmlFor={id} className={isAuth ? authLabelBase : labelBase}>
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
                <input
                    ref={isDateInput ? inputRef : undefined}
                    id={id}
                    className={fieldClass}
                    aria-invalid={error ? true : undefined}
                    required={required}
                    {...props}
                />
            )}
            {error ? (
                <p id={`${id}-error`} className={isAuth ? authErrorText : errorText} role="alert">
                    {error}
                </p>
            ) : helper ? (
                <p id={`${id}-helper`} className={isAuth ? authHelperText : helperText}>
                    {helper}
                </p>
            ) : null}
        </div>
    );
}

/** Shared select styling for public/account forms (use on native &lt;select&gt;). */
export const selectControlClass = controlBase;
