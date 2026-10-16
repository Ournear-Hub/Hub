import { forwardRef, type InputHTMLAttributes, type MouseEvent } from 'react';
import { adminControlBase, controlBase, controlError } from '@/design-system/classNames';

export type InputSurface = 'public' | 'account' | 'admin' | 'corporate';

export type InputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'className'> & {
    surface?: InputSurface;
    /** Invalid state — border/focus only; message is parent/FormField responsibility. */
    error?: boolean;
    className?: string;
};

function isAdminFamily(surface: InputSurface): boolean {
    return surface === 'admin' || surface === 'corporate';
}

function isPickerType(type: InputHTMLAttributes<HTMLInputElement>['type']): boolean {
    return type === 'date' || type === 'datetime-local' || type === 'time' || type === 'month' || type === 'week';
}

function openPicker(event: MouseEvent<HTMLInputElement>) {
    const input = event.currentTarget;
    if (typeof input.showPicker !== 'function' || input.disabled || input.readOnly) {
        return;
    }

    try {
        input.showPicker();
    } catch {
        // Native picker still works if showPicker is blocked.
    }
}

function controlClasses(surface: InputSurface, error: boolean, picker: boolean): string {
    const base = isAdminFamily(surface) ? adminControlBase : controlBase;
    const errorClass = error ? controlError : '';
    const pickerClass = picker ? 'cursor-pointer' : '';
    return `${base} ${errorClass} ${pickerClass}`.trim();
}

const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
    { surface = 'public', error = false, className = '', type, onClick, 'aria-invalid': ariaInvalid, ...props },
    ref,
) {
    const picker = isPickerType(type);
    const invalid = error || ariaInvalid === true || ariaInvalid === 'true';

    return (
        <input
            ref={ref}
            type={type}
            className={`${controlClasses(surface, error, picker)} ${className}`.trim()}
            aria-invalid={invalid ? true : ariaInvalid}
            onClick={(event) => {
                if (picker) {
                    openPicker(event);
                }
                onClick?.(event);
            }}
            {...props}
        />
    );
});

export default Input;
