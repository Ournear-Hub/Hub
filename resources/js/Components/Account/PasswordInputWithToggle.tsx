import { useState, type InputHTMLAttributes } from 'react';
import IconButton from '@/Components/atoms/IconButton';
import {
    authControlBase,
    authControlError,
    authErrorText,
    authLabelBase,
    controlBase,
    controlError,
    errorText,
    labelBase,
} from '@/design-system/classNames';

type Props = {
    id: string;
    label: string;
    error?: string;
    required?: boolean;
    appearance?: 'default' | 'auth';
    placeholder?: string;
} & Omit<InputHTMLAttributes<HTMLInputElement>, 'className' | 'type' | 'placeholder'>;

export default function PasswordInputWithToggle({
    id,
    label,
    error,
    required,
    appearance = 'default',
    placeholder,
    ...props
}: Props) {
    const [visible, setVisible] = useState(false);
    const isAuth = appearance === 'auth';
    const labelClass = isAuth ? authLabelBase : labelBase;
    const base = isAuth ? authControlBase : controlBase;
    const err = isAuth ? authControlError : controlError;
    const fieldClass = [base, 'pr-11', error ? err : '']
        .filter(Boolean)
        .join(' ');

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
            <div className="relative">
                <input
                    id={id}
                    type={visible ? 'text' : 'password'}
                    className={fieldClass}
                    aria-invalid={error ? true : undefined}
                    required={required}
                    placeholder={placeholder}
                    {...props}
                />
                <IconButton
                    type="button"
                    label={visible ? 'Hide password' : 'Show password'}
                    icon={visible ? 'eye-off' : 'eye'}
                    className="absolute right-1 top-1/2 -translate-y-1/2"
                    onClick={() => setVisible((current) => !current)}
                />
            </div>
            {error ? (
                <p id={`${id}-error`} className={isAuth ? authErrorText : errorText} role="alert">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
