import { forwardRef, type TextareaHTMLAttributes } from 'react';
import type { InputSurface } from '@/Components/atoms/Input';
import { adminControlBase, controlBase, controlError } from '@/design-system/classNames';

export type TextareaProps = Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'className'> & {
    surface?: InputSurface;
    error?: boolean;
    className?: string;
};

function isAdminFamily(surface: InputSurface): boolean {
    return surface === 'admin' || surface === 'corporate';
}

/** Matches legacy admin Textarea (`min-h-24`). Public FormField uses `min-h-28`. */
function minHeightClass(surface: InputSurface): string {
    return isAdminFamily(surface) ? 'min-h-24' : 'min-h-28';
}

function controlClasses(surface: InputSurface, error: boolean): string {
    const base = isAdminFamily(surface) ? adminControlBase : controlBase;
    const errorClass = error ? controlError : '';
    return `${base} ${minHeightClass(surface)} ${errorClass}`.trim();
}

const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
    { surface = 'public', error = false, className = '', 'aria-invalid': ariaInvalid, ...props },
    ref,
) {
    const invalid = error || ariaInvalid === true || ariaInvalid === 'true';

    return (
        <textarea
            ref={ref}
            className={`${controlClasses(surface, error)} ${className}`.trim()}
            aria-invalid={invalid ? true : ariaInvalid}
            {...props}
        />
    );
});

export default Textarea;
