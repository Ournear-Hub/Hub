import { forwardRef, type InputHTMLAttributes } from 'react';
import type { InputSurface } from '@/Components/atoms/Input';
import { radioAdminBase, radioBase, radioError } from '@/design-system/classNames';

export type RadioProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'className'> & {
    surface?: InputSurface;
    error?: boolean;
    className?: string;
};

function isAdminFamily(surface: InputSurface): boolean {
    return surface === 'admin' || surface === 'corporate';
}

function radioClasses(surface: InputSurface, error: boolean): string {
    const base = isAdminFamily(surface) ? radioAdminBase : radioBase;
    const errorClass = error ? radioError : '';
    return `${base} ${errorClass}`.trim();
}

const Radio = forwardRef<HTMLInputElement, RadioProps>(function Radio(
    { surface = 'public', error = false, className = '', 'aria-invalid': ariaInvalid, ...props },
    ref,
) {
    const invalid = error || ariaInvalid === true || ariaInvalid === 'true';

    return (
        <input
            ref={ref}
            type="radio"
            className={`${radioClasses(surface, error)} ${className}`.trim()}
            aria-invalid={invalid ? true : ariaInvalid}
            {...props}
        />
    );
});

export default Radio;
