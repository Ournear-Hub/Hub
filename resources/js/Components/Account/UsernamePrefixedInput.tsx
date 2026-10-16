import type { InputHTMLAttributes } from 'react';
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

type Props = {
    id: string;
    label: string;
    helper?: string;
    error?: string;
    required?: boolean;
    appearance?: 'default' | 'auth';
} & Omit<InputHTMLAttributes<HTMLInputElement>, 'className'>;

export default function UsernamePrefixedInput({
    id,
    label,
    helper,
    error,
    required,
    appearance = 'default',
    ...props
}: Props) {
    const isAuth = appearance === 'auth';
    const labelClass = isAuth ? authLabelBase : labelBase;
    const base = isAuth ? authControlBase : controlBase;
    const err = isAuth ? authControlError : controlError;
    const fieldClass = [base, 'rounded-l-none border-l-0 pl-3', error ? err : ''].filter(Boolean).join(' ');
    const prefixClass = isAuth
        ? 'inline-flex min-h-[2.875rem] items-center rounded-l-lg border border-r-0 border-[#D5DFE0] bg-white px-3 text-sm font-semibold text-brand'
        : 'inline-flex min-h-[2.625rem] items-center rounded-l-md border border-r-0 border-line bg-canvas px-3 text-sm font-semibold text-accent';

    return (
        <div>
            <label htmlFor={id} className={labelClass}>
                {label}
                {required ? (
                    <span className="text-danger" aria-hidden="true">
                        {' '}
                        *
                    </span>
                ) : null}
            </label>
            <div className="flex">
                <span className={prefixClass} aria-hidden="true">
                    ^
                </span>
                <input id={id} className={fieldClass} aria-invalid={error ? true : undefined} required={required} {...props} />
            </div>
            {helper ? <p className={isAuth ? authHelperText : helperText}>{helper}</p> : null}
            {error ? (
                <p id={`${id}-error`} className={isAuth ? authErrorText : errorText} role="alert">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
