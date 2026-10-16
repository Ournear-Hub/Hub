import Input, { type InputProps, type InputSurface } from '@/Components/atoms/Input';
import { forwardRef } from 'react';

export type DateFieldProps = Omit<InputProps, 'type' | 'className'> & {
    /** Optional wrapper (layout only). */
    className?: string;
    /** Applied to the native date input (canonical Input). */
    inputClassName?: string;
};

/**
 * Native `type="date"` form control — styling and showPicker via canonical Input.
 * Does not own value, parsing, or calendar UI.
 */
const DateField = forwardRef<HTMLInputElement, DateFieldProps>(function DateField(
    { surface = 'public', error = false, className = '', inputClassName = '', ...inputProps },
    ref,
) {
    const inputClasses = inputClassName.trim() || undefined;

    const input = (
        <Input
            ref={ref}
            type="date"
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

export default DateField;
