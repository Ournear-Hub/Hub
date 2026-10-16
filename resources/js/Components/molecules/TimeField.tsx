import Input, { type InputProps, type InputSurface } from '@/Components/atoms/Input';
import { forwardRef } from 'react';

export type TimeFieldProps = Omit<InputProps, 'type' | 'className'> & {
    /** Optional wrapper (layout only). */
    className?: string;
    /** Applied to the native time input (canonical Input). */
    inputClassName?: string;
};

/**
 * Native `type="time"` form control — styling and showPicker via canonical Input.
 * Does not own value, parsing, or clock UI.
 */
const TimeField = forwardRef<HTMLInputElement, TimeFieldProps>(function TimeField(
    { surface = 'public', error = false, className = '', inputClassName = '', ...inputProps },
    ref,
) {
    const inputClasses = inputClassName.trim() || undefined;

    const input = (
        <Input
            ref={ref}
            type="time"
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

export default TimeField;
