import { forwardRef, type SelectHTMLAttributes } from 'react';
import type { InputSurface } from '@/Components/atoms/Input';
import { adminControlBase, controlBase, controlError } from '@/design-system/classNames';

export type SelectProps = Omit<SelectHTMLAttributes<HTMLSelectElement>, 'className'> & {
    surface?: InputSurface;
    error?: boolean;
    className?: string;
};

function isAdminFamily(surface: InputSurface): boolean {
    return surface === 'admin' || surface === 'corporate';
}

function controlClasses(surface: InputSurface, error: boolean): string {
    const base = isAdminFamily(surface) ? adminControlBase : controlBase;
    const errorClass = error ? controlError : '';
    return `${base} ${errorClass}`.trim();
}

const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
    { surface = 'public', error = false, className = '', children, 'aria-invalid': ariaInvalid, ...props },
    ref,
) {
    const invalid = error || ariaInvalid === true || ariaInvalid === 'true';

    return (
        <select
            ref={ref}
            className={`${controlClasses(surface, error)} ${className}`.trim()}
            aria-invalid={invalid ? true : ariaInvalid}
            {...props}
        >
            {children}
        </select>
    );
});

export default Select;
