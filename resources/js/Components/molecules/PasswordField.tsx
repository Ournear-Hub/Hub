import IconButton from '@/Components/atoms/IconButton';
import Input, { type InputProps, type InputSurface } from '@/Components/atoms/Input';
import { forwardRef, useRef, useState, type ForwardedRef } from 'react';

export type PasswordFieldProps = Omit<InputProps, 'type' | 'className'> & {
    className?: string;
    inputClassName?: string;
    /** Show visibility toggle (default true). V2 auth forms use plain password inputs without toggle. */
    showToggle?: boolean;
};

function mergeInputRef(ref: ForwardedRef<HTMLInputElement>, node: HTMLInputElement | null) {
    if (typeof ref === 'function') {
        ref(node);
    } else if (ref) {
        ref.current = node;
    }
}

const PasswordField = forwardRef<HTMLInputElement, PasswordFieldProps>(function PasswordField(
    {
        surface = 'public',
        error = false,
        showToggle = true,
        className = '',
        inputClassName = '',
        disabled,
        ...inputProps
    },
    ref,
) {
    const [visible, setVisible] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    const toggleVisible = () => {
        setVisible((current) => !current);
        requestAnimationFrame(() => inputRef.current?.focus());
    };

    const paddingRight = showToggle && !disabled ? 'pr-10' : '';

    return (
        <div className={`relative ${className}`.trim()}>
            <Input
                ref={(node) => {
                    inputRef.current = node;
                    mergeInputRef(ref, node);
                }}
                type={visible ? 'text' : 'password'}
                surface={surface}
                error={error}
                disabled={disabled}
                className={`${paddingRight} ${inputClassName}`.trim()}
                {...inputProps}
            />
            {showToggle && !disabled ? (
                <div className="absolute right-0.5 top-1/2 z-[1] -translate-y-1/2">
                    <IconButton
                        type="button"
                        surface={surface}
                        variant="ghost"
                        size="sm"
                        icon={visible ? 'eye-off' : 'eye'}
                        label={visible ? 'Hide password' : 'Show password'}
                        onClick={toggleVisible}
                    />
                </div>
            ) : null}
        </div>
    );
});

export type { InputSurface };

export default PasswordField;
