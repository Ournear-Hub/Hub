import { forwardRef, type InputHTMLAttributes } from 'react';
import type { InputSurface } from '@/Components/atoms/Input';
import { checkboxAdminBase, checkboxBase, checkboxError } from '@/design-system/classNames';

export type CheckboxProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'className'> & {
    surface?: InputSurface;
    error?: boolean;
    className?: string;
};

function isAdminFamily(surface: InputSurface): boolean {
    return surface === 'admin' || surface === 'corporate';
}

function checkboxClasses(surface: InputSurface, error: boolean): string {
    const base = isAdminFamily(surface) ? checkboxAdminBase : checkboxBase;
    const errorClass = error ? checkboxError : '';
    return `${base} ${errorClass}`.trim();
}

const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(function Checkbox(
    { surface = 'public', error = false, className = '', 'aria-invalid': ariaInvalid, ...props },
    ref,
) {
    const invalid = error || ariaInvalid === true || ariaInvalid === 'true';

    return (
        <input
            ref={ref}
            type="checkbox"
            className={`${checkboxClasses(surface, error)} ${className}`.trim()}
            aria-invalid={invalid ? true : ariaInvalid}
            {...props}
        />
    );
});

export default Checkbox;
