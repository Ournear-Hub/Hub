import Input, { type InputProps, type InputSurface } from '@/Components/atoms/Input';
import { forwardRef } from 'react';

export type URLFieldProps = Omit<InputProps, 'type' | 'className'> & {
    /** Optional wrapper (layout only). */
    className?: string;
    /** Applied to the native URL input (canonical Input). */
    inputClassName?: string;
};

/**
 * Native `type="url"` form control — styling via canonical Input.
 * Does not validate, normalize, or own URL value state.
 */
const URLField = forwardRef<HTMLInputElement, URLFieldProps>(function URLField(
    { surface = 'public', error = false, className = '', inputClassName = '', ...inputProps },
    ref,
) {
    const inputClasses = inputClassName.trim() || undefined;

    const input = (
        <Input
            ref={ref}
            type="url"
            surface={surface}
            error={error}
            className={inputClasses}
            {...inputProps}
        />
    );

    if (className.trim()) {
        return <div className={className.trim()}>{input}</div>;
    }

    return input;
});

export type { InputSurface };

export default URLField;
