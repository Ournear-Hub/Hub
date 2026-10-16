import Input, { type InputProps, type InputSurface } from '@/Components/atoms/Input';
import { forwardRef } from 'react';

export type DateTimeFieldProps = Omit<InputProps, 'type' | 'className'> & {
    /** Optional wrapper (layout only). */
    className?: string;
    /** Applied to the native datetime-local input (canonical Input). */
    inputClassName?: string;
};

/**
 * Native `type="datetime-local"` form control — styling and showPicker via canonical Input.
 * Does not own value, parsing, or picker UI. Separate from DateField and TimeField.
 */
const DateTimeField = forwardRef<HTMLInputElement, DateTimeFieldProps>(function DateTimeField(
    { surface = 'public', error = false, className = '', inputClassName = '', ...inputProps },
    ref,
) {
    const inputClasses = inputClassName.trim() || undefined;

    const input = (
        <Input
            ref={ref}
            type="datetime-local"
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

export default DateTimeField;
