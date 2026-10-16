import Input, { type InputProps, type InputSurface } from '@/Components/atoms/Input';
import { forwardRef } from 'react';

export type NumberFieldProps = Omit<InputProps, 'type' | 'className'> & {
    /** Optional wrapper (layout only). */
    className?: string;
    /** Applied to the native number input (canonical Input). */
    inputClassName?: string;
};

/**
 * Native `type="number"` form control — styling via canonical Input.
 * Does not own value, formatting, or spinner UI.
 */
const NumberField = forwardRef<HTMLInputElement, NumberFieldProps>(function NumberField(
    { surface = 'public', error = false, className = '', inputClassName = '', ...inputProps },
    ref,
) {
    const inputClasses = inputClassName.trim() || undefined;

    const input = (
        <Input
            ref={ref}
            type="number"
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

export default NumberField;
